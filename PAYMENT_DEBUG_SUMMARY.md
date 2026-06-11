# Payment Integration - Debugging Report & Summary

## 🎯 Problem Identified

**Root Cause:** Foreign Key Constraint Violation  
Payment endpoint gagal karena:

1. **Validation Error**: Android mengirim `booking_id` sebagai string, tapi backend validasi `required|integer`
2. **Foreign Key Constraint**: Database table `payments` memiliki foreign key ke `bookings` table, jadi `booking_id` harus:
   - Berupa integer
   - Sudah terdaftar di tabel `bookings` (jika dikirim)
   - Atau null (jika booking belum dibuat)

---

## ✅ Fixes Applied

### 1. **PaymentController.php** - Validasi Request
```php
// BEFORE (❌ Error 422)
'booking_id' => 'required|integer',

// AFTER (✅ Flexible)
'booking_id' => 'nullable',
```

### 2. **PaymentController.php** - Null Coalescing
```php
// Handle case ketika booking_id tidak dikirim
$bookingId = $validated['booking_id'] ?? null;
```

### 3. **PaymentController.php** - String to Integer Conversion
```php
$finalBookingId = null;
if ($bookingId) {
    if (is_numeric($bookingId)) {
        $finalBookingId = (int) $bookingId;
        
        // Validasi booking exists di database
        $bookingExists = DB::table('bookings')
            ->where('booking_id', $finalBookingId)
            ->exists();
        if (!$bookingExists) {
            return response()->json([
                'success' => false,
                'message' => 'Booking ID tidak ditemukan di sistem'
            ], 422);
        }
    }
}
```

---

## 🧪 Test Results

Semua test cases berhasil:

### ✅ Test 1: Integer booking_id
```
Request:  { "booking_id": 1, "total_price": 100000, "payment_method": "bank_transfer" }
Response: { "success": true, "token": "aefcada3...", "order_id": "WOOSH-24" }
Status:   200 OK ✓
```

### ✅ Test 2: String numeric booking_id
```
Request:  { "booking_id": "1", "total_price": 50000, "payment_method": "ewallet" }
Response: { "success": true, "token": "6fb54c85...", "order_id": "WOOSH-25" }
Status:   200 OK ✓
```

### ✅ Test 3: Null/Missing booking_id
```
Request:  { "total_price": 75000, "payment_method": "bank_transfer" }
Response: { "success": true, "token": "81763a3a...", "order_id": "WOOSH-26" }
Status:   200 OK ✓
```

### ✅ Test 4: Invalid booking_id (not in database)
```
Request:  { "booking_id": "999", "total_price": 100000, "payment_method": "credit_card" }
Response: { "success": false, "message": "Booking ID tidak ditemukan di sistem" }
Status:   422 Unprocessable Content ✓
```

---

## 📋 Android Checklist

Pastikan Android app memenuhi kriteria ini:

- [ ] **Request Format** 
  - Jika ada booking: Kirim `booking_id` sebagai integer atau string numeric
  - Jika belum ada booking: Abaikan field `booking_id` atau kirim `null`
  - Contoh: `{ "booking_id": 1, "total_price": 100000, "payment_method": "bank_transfer" }`

- [ ] **Payment Methods**
  - Hanya gunakan: `bank_transfer`, `ewallet`, `credit_card`
  - Jangan kirim value lain

- [ ] **Amount Validation**
  - `total_price` harus integer minimal 1
  - Format: `100000` (untuk Rp 100.000)
  - Jangan: `"100000.00"` (string dengan decimal)

- [ ] **Error Handling**
  - Handle HTTP 422 → Validation error, tampilkan pesan ke user
  - Handle HTTP 500 → Server error, tampilkan "Gagal membuat sesi pembayaran"
  - Always check `response.isSuccessful` sebelum akses body

- [ ] **WebView Integration**
  - Setelah dapat `redirect_url`, buka di WebView atau browser
  - User akan di-redirect ke Midtrans payment page
  - Tunggu user selesai payment atau cancel

---

## 🔗 Related Files

- **Backend**: [app/Http/Controllers/Api/PaymentController.php](app/Http/Controllers/Api/PaymentController.php)
- **Model**: [app/Models/Payment.php](app/Models/Payment.php)  
- **Routes**: [routes/api.php](routes/api.php)
- **Documentation**: [API_PAYMENT_DOCS.md](API_PAYMENT_DOCS.md)
- **Database Schema**: See `payments` table structure in tinker output

---

## 🚀 Next Steps

1. **Android Developer**: Update ApiService.kt & PaymentRequest model sesuai checklist
2. **Testing**: Lakukan end-to-end test dari SeatActivity → Payment → Midtrans
3. **Production**: Ganti `MIDTRANS_IS_PRODUCTION=false` ke `true` di .env production

---

## 📞 Troubleshooting

**Jika masih error "Gagal membuat sesi pembayaran":**

1. Check Android request format → lihat dokumentasi di `API_PAYMENT_DOCS.md`
2. Check laravel.log → jalankan: `tail -f storage/logs/laravel.log`
3. Verify `booking_id` ada di database → jalankan di tinker: `DB::table('bookings')->get()`
4. Check Midtrans credentials di .env → pastikan `MIDTRANS_SERVER_KEY` dan `MIDTRANS_IS_PRODUCTION` benar

---

**Status**: ✅ RESOLVED  
**Date**: 2026-06-11  
**Tested by**: Backend Developer
