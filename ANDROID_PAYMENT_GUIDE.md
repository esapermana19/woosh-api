# Android Implementation Guide - Payment Feature Fix

## Problem yang Sudah Diidentifikasi ✅

Error: **"Gagal membuat sesi pembayaran server"**

**Root Cause**: Request format tidak sesuai backend expectation + Foreign Key Constraint

Backend sudah di-fix untuk handle:
- ✅ `booking_id` sebagai string/integer/null
- ✅ Validasi booking exists di database
- ✅ Error message yang jelas untuk setiap scenario

---

## ✅ Checklist untuk Android Implementation

### 1. **Payment Request Model**

Pastikan `PaymentRequest` (atau `CheckoutRequest`) memiliki struktur:

```kotlin
@Serializable  // atau @Parcelize
data class PaymentRequest(
    val booking_id: Int? = null,       // ✅ Nullable int
    val total_price: Int,              // ✅ Integer (Rp)
    val payment_method: String         // ✅ String enum
)
```

**Catatan:**
- `booking_id`: Integer nullable (bisa null jika belum ada booking)
- `total_price`: Integer dalam rupiah (misal: `100000` untuk Rp 100.000)
- `payment_method`: Salah satu dari `["bank_transfer", "ewallet", "credit_card"]`

### 2. **API Service Definition**

```kotlin
interface ApiService {
    @POST("api/payment/checkout")
    fun createPayment(@Body request: PaymentRequest): Call<PaymentResponse>
}
```

**Response Model:**

```kotlin
@Serializable
data class PaymentResponse(
    val success: Boolean,
    val redirect_url: String? = null,
    val token: String? = null,
    val order_id: String? = null,
    val message: String? = null
)
```

### 3. **SeatActivity.kt - Payment Button Logic**

Update button click handler untuk send correct format:

```kotlin
// Ketika user click "Bayar Sekarang" button
payButton.setOnClickListener {
    val bookingId = getCurrentBookingId()  // Get dari database/previous activity
    val totalPrice = calculateTotalPrice() // Get dari seat selection
    val paymentMethod = selectedPaymentMethod // "bank_transfer" atau "ewallet" etc
    
    // Create payment request
    val paymentRequest = PaymentRequest(
        booking_id = bookingId,     // Bisa null jika belum ada booking
        total_price = totalPrice,   // Integer dalam Rp
        payment_method = paymentMethod
    )
    
    // Call API
    apiService.createPayment(paymentRequest)
        .enqueue(object : Callback<PaymentResponse> {
            override fun onResponse(
                call: Call<PaymentResponse>,
                response: Response<PaymentResponse>
            ) {
                if (response.isSuccessful && response.body()?.success == true) {
                    val redirectUrl = response.body()?.redirect_url
                    if (redirectUrl != null) {
                        // Buka payment page
                        openPaymentPage(redirectUrl)
                    }
                } else {
                    // Handle error
                    val errorMessage = response.body()?.message 
                        ?: "Gagal membuat sesi pembayaran"
                    Toast.makeText(this@SeatActivity, errorMessage, Toast.LENGTH_SHORT).show()
                }
            }

            override fun onFailure(call: Call<PaymentResponse>, t: Throwable) {
                Toast.makeText(
                    this@SeatActivity,
                    "Gagal membuat sesi pembayaran: ${t.message}",
                    Toast.LENGTH_SHORT
                ).show()
            }
        })
}

private fun openPaymentPage(redirectUrl: String) {
    val intent = Intent(Intent.ACTION_VIEW, Uri.parse(redirectUrl))
    startActivity(intent)
    // Atau gunakan WebView:
    // val webView = WebView(this)
    // webView.loadUrl(redirectUrl)
}
```

### 4. **Booking ID Handling**

**Scenario 1: Jika sudah ada booking**
```kotlin
// booking_id sudah terdaftar di database
val bookingId = sharedPreferences.getInt("booking_id", -1)
if (bookingId != -1) {
    // Kirim booking_id
    val request = PaymentRequest(
        booking_id = bookingId,
        total_price = amount,
        payment_method = method
    )
}
```

**Scenario 2: Jika belum ada booking (direct checkout)**
```kotlin
// Belum membuat booking, langsung checkout
val request = PaymentRequest(
    booking_id = null,  // Atau abaikan field ini
    total_price = amount,
    payment_method = method
)
```

### 5. **Payment Method Selection**

Pastikan enum yang dikirim sesuai:

```kotlin
enum class PaymentMethod(val value: String) {
    BANK_TRANSFER("bank_transfer"),
    EWALLET("ewallet"),
    CREDIT_CARD("credit_card")
}

// Ketika user pilih payment method:
val selectedMethod = PaymentMethod.BANK_TRANSFER.value  // "bank_transfer"
```

### 6. **Error Handling**

Handle semua error cases:

```kotlin
apiService.createPayment(request).enqueue(object : Callback<PaymentResponse> {
    override fun onResponse(call: Call<PaymentResponse>, response: Response<PaymentResponse>) {
        when {
            response.isSuccessful && response.body()?.success == true -> {
                // ✅ Payment session created successfully
                val redirectUrl = response.body()?.redirect_url
                openPaymentPage(redirectUrl!!)
            }
            response.code() == 422 -> {
                // ❌ Validation error (invalid request format)
                val message = response.body()?.message 
                    ?: "Format request tidak valid"
                Toast.makeText(context, message, Toast.LENGTH_LONG).show()
            }
            response.code() == 500 -> {
                // ❌ Server error
                Toast.makeText(
                    context, 
                    "Server error. Hubungi admin", 
                    Toast.LENGTH_SHORT
                ).show()
            }
            else -> {
                // ❌ Unknown error
                Toast.makeText(
                    context,
                    "Gagal membuat sesi pembayaran",
                    Toast.LENGTH_SHORT
                ).show()
            }
        }
    }

    override fun onFailure(call: Call<PaymentResponse>, t: Throwable) {
        // ❌ Network/connection error
        Toast.makeText(
            context,
            "Koneksi error: ${t.message}",
            Toast.LENGTH_SHORT
        ).show()
    }
})
```

### 7. **Data Type Conversion**

⚠️ **IMPORTANT - Common Mistakes:**

```kotlin
// ❌ WRONG
val totalPrice = "100000"  // String - akan error validasi
val request = PaymentRequest(
    booking_id = 1,
    total_price = totalPrice.toInt(),  // Harus convert dulu!
    payment_method = "bank_transfer"
)

// ✅ CORRECT
val totalPrice = 100000  // Integer
val request = PaymentRequest(
    booking_id = 1,
    total_price = totalPrice,
    payment_method = "bank_transfer"
)
```

### 8. **Retrofit Interceptor Check**

Pastikan Retrofit client setup dengan benar:

```kotlin
val retrofit = Retrofit.Builder()
    .baseUrl("http://YOUR_BACKEND_URL/")
    .addConverterFactory(Json.asConverterFactory("application/json".toMediaType()))
    .client(httpClient)
    .build()

val apiService = retrofit.create(ApiService::class.java)
```

---

## 🧪 Testing Checklist

Lakukan testing dengan skenario berikut:

- [ ] Test dengan `booking_id` integer (misal: `1`)
- [ ] Test dengan `booking_id` string numeric (misal: `"1"`)
- [ ] Test tanpa `booking_id` (null atau abaikan field)
- [ ] Test dengan invalid `booking_id` (misal: `999`) → harus error
- [ ] Test dengan invalid `payment_method` → harus error
- [ ] Test dengan `total_price` = 0 → harus error
- [ ] Test network error → harus handle gracefully
- [ ] Test success → harus open payment page dengan `redirect_url`

---

## 🔗 Backend API Endpoints

### Create Payment Transaction
```
POST /api/payment/checkout
Content-Type: application/json

{
  "booking_id": 1,
  "total_price": 100000,
  "payment_method": "bank_transfer"
}
```

### Webhook (Automatic - No need to call from Android)
```
POST /api/payment/notification
```
Backend akan handle Midtrans webhook automatically.

---

## 📚 Documentation Files

Backend sudah provide dokumentasi lengkap:
- **[API_PAYMENT_DOCS.md](../API_PAYMENT_DOCS.md)** - Complete API reference
- **[PAYMENT_DEBUG_SUMMARY.md](../PAYMENT_DEBUG_SUMMARY.md)** - Debug info & test results

---

## ⚠️ Common Issues & Solutions

### Issue 1: "Gagal membuat sesi pembayaran server"
**Solution**: 
- Check request format di network logger
- Verify `total_price` adalah integer
- Verify `payment_method` salah satu dari enum values

### Issue 2: "Booking ID tidak ditemukan di sistem"
**Solution**:
- Pastikan booking sudah dibuat sebelum payment
- Atau kirim `booking_id = null` jika belum ada booking

### Issue 3: Network Error / Connection Failed
**Solution**:
- Verify backend URL di ApiService
- Check internet connection
- Verify backend server is running (`php artisan serve`)

### Issue 4: WebView/Browser tidak buka redirect_url
**Solution**:
- Verify `response.body()?.redirect_url` tidak null
- Check logcat untuk error handling code
- Pastikan Intent.ACTION_VIEW permissions di AndroidManifest.xml

---

## 📞 Need Help?

1. Check [API_PAYMENT_DOCS.md](../API_PAYMENT_DOCS.md) untuk endpoint reference
2. Check [PAYMENT_DEBUG_SUMMARY.md](../PAYMENT_DEBUG_SUMMARY.md) untuk test results
3. Check laravel.log di backend: `tail -f storage/logs/laravel.log`
4. Test API dengan cURL/Postman sebelum integrate ke Android

---

**Version**: 1.0  
**Last Updated**: 2026-06-11  
**Status**: ✅ Ready for Implementation
