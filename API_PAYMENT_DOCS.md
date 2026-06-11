# Payment API Documentation untuk Android Developer

## Endpoint: POST `/api/payment/checkout`

Endpoint ini membuat session pembayaran di Midtrans dan menyimpan record pembayaran ke database dengan status `pending`.

### Request Format

```json
{
  "booking_id": 1,
  "total_price": 100000,
  "payment_method": "bank_transfer"
}
```

### Parameter

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `booking_id` | integer or string | Optional | ID booking dari database. Jika belum ada booking, kirim null atau abaikan field ini. Jika dikirim, harus berupa ID yang sudah terdaftar di tabel `bookings`. |
| `total_price` | integer/decimal | **Required** | Total harga pembayaran dalam rupiah (minimal: 1) |
| `payment_method` | string | **Required** | Metode pembayaran. Pilih salah satu: `bank_transfer`, `ewallet`, `credit_card` |

### Response Success (200)

```json
{
  "success": true,
  "redirect_url": "https://app.sandbox.midtrans.com/snap/v4/redirection/...",
  "token": "aefcada3-2173-41fe-ad00-00291cb7ea9a",
  "order_id": "WOOSH-24"
}
```

**Keterangan:**
- `redirect_url`: URL untuk redirect user ke payment page Midtrans
- `token`: Snap token untuk integrate payment di frontend (jika menggunakan Snap.js)
- `order_id`: Order ID unique di Midtrans (format: `WOOSH-{payment_id}`)

### Response Error

**Error 422** - Validation Error
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "total_price": ["The total price field is required."],
    "payment_method": ["The payment method field must be one of: bank_transfer, ewallet, credit_card."]
  }
}
```

**Error 422** - Booking Not Found
```json
{
  "success": false,
  "message": "Booking ID tidak ditemukan di sistem"
}
```

**Error 500** - Server Error
```json
{
  "success": false,
  "message": "Konfigurasi server pembayaran tidak valid"
}
```

---

## Cara Penggunaan di Android Studio

### 1. Minimal Request (Tanpa Booking ID)

Gunakan ini jika user belum membuat booking sebelumnya, hanya mau checkout langsung.

**Kotlin:**
```kotlin
val request = PaymentRequest(
    booking_id = null,
    total_price = 100000,
    payment_method = "bank_transfer"
)
apiService.createPayment(request)
    .enqueue(object : Callback<PaymentResponse> {
        override fun onResponse(call: Call<PaymentResponse>, response: Response<PaymentResponse>) {
            if (response.isSuccessful) {
                val redirectUrl = response.body()?.redirect_url
                // Buka redirect_url di WebView atau browser
            }
        }
        override fun onFailure(call: Call<PaymentResponse>, t: Throwable) {
            Toast.makeText(context, "Gagal membuat sesi pembayaran: ${t.message}", Toast.LENGTH_SHORT).show()
        }
    })
```

### 2. Request dengan Booking ID

Gunakan ini jika user sudah memilih jadwal kereta & tempat duduk (berarti booking sudah dibuat).

**Kotlin:**
```kotlin
val request = PaymentRequest(
    booking_id = 1,  // ID dari booking yang sudah dibuat
    total_price = 150000,
    payment_method = "ewallet"
)
apiService.createPayment(request).enqueue(...)
```

### 3. Data Model

```kotlin
data class PaymentRequest(
    val booking_id: Int? = null,
    val total_price: Int,
    val payment_method: String
)

data class PaymentResponse(
    val success: Boolean,
    val redirect_url: String,
    val token: String,
    val order_id: String
)
```

### 4. ApiService Interface

```kotlin
interface ApiService {
    @POST("api/payment/checkout")
    fun createPayment(@Body request: PaymentRequest): Call<PaymentResponse>
}
```

---

## Payment Status Flow

1. **Pending** - Saat payment baru dibuat (initial state)
2. **Success** - Saat user berhasil melakukan pembayaran di Midtrans
3. **Failed** - Saat pembayaran ditolak atau timeout

Status update dilakukan melalui Webhook dari Midtrans (otomatis).

---

## Testing dengan cURL

```bash
# Test 1: Request tanpa booking_id
curl -X POST http://localhost:8000/api/payment/checkout \
  -H "Content-Type: application/json" \
  -d '{
    "total_price": 100000,
    "payment_method": "bank_transfer"
  }'

# Test 2: Request dengan booking_id
curl -X POST http://localhost:8000/api/payment/checkout \
  -H "Content-Type: application/json" \
  -d '{
    "booking_id": 1,
    "total_price": 150000,
    "payment_method": "ewallet"
  }'

# Test 3: Request dengan booking_id string
curl -X POST http://localhost:8000/api/payment/checkout \
  -H "Content-Type: application/json" \
  -d '{
    "booking_id": "1",
    "total_price": 75000,
    "payment_method": "credit_card"
  }'
```

---

## Important Notes

⚠️ **Jangan lupa:**
1. Pastikan `booking_id` (jika dikirim) adalah integer atau string numeric yang valid
2. `total_price` harus integer/decimal minimal 1 (minimal transaksi: Rp 1)
3. `payment_method` harus salah satu dari: `bank_transfer`, `ewallet`, `credit_card`
4. Jika `booking_id` dikirim, booking dengan ID tersebut harus sudah terdaftar di database
5. Handle redirect_url dengan membuka di WebView atau browser untuk user melakukan pembayaran

---

## Database Schema Reference

### Table: payments
```
payment_id        : int (auto-increment, primary key)
booking_id        : int (nullable, foreign key ke bookings table)
payment_method    : enum('bank_transfer','ewallet','credit_card')
payment_date      : timestamp
amount            : decimal(12,2)
payment_status    : enum('pending','success','failed')
```

---

**Last Updated:** 2026-06-11  
**Backend Version:** Laravel 11 with Midtrans Snap API  
**API Base URL:** http://localhost:8000 (Development)
