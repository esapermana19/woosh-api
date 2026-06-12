# 🚀 Quick Start - Navigation Menu Integration

**Untuk Tim Android - Bacalah ini terlebih dahulu!**

---

## 📌 Apa yang sudah disiapkan backend?

Backend sudah siap dengan 2 API endpoint untuk navigasi menu:

### 1️⃣ Get All Tickets (Semua Riwayat)
```
GET /api/tickets/history
Headers: Authorization: Bearer {token}
Response: List semua booking user dengan status pembayaran
```

### 2️⃣ Get Filtered Tickets (Filter Status)
```
GET /api/tickets/history-filtered?filter=pending
Headers: Authorization: Bearer {token}
Query Params:
  - filter=all        → Semua booking
  - filter=pending    → Belum dibayar
  - filter=paid       → Sudah terbayar
  - filter=failed     → Gagal pembayaran
  - filter=completed  → Selesai
```

**Response**: Sama-sama data booking dengan detail lengkap

---

## 🎯 Apa yang perlu dibuat Android?

### ✨ UI Elements

```
┌─────────────────────────────┐
│  Navigation Menu (BottomBar)│
├──────────┬──────────────────┤
│ 🏠 Home  │ 🎫 My Tickets     │
└──────────┴──────────────────┘
     ↓           ↓
┌──────────┐  ┌──────────────────────┐
│Booking   │  │ Filter Tabs:         │
│Screen    │  │ [All] [Pending]...   │
│          │  │                      │
│Search    │  │ ┌──────────────────┐ │
│Schedule  │  │ │ Booking Card:    │ │
│          │  │ │ - Code           │ │
│          │  │ │ - Train Name     │ │
│          │  │ │ - Departure      │ │
│          │  │ │ - Arrival        │ │
│          │  │ │ - Status         │ │
│          │  │ │ - Price          │ │
│          │  │ │ - Passengers     │ │
│          │  │ │ [Lihat QR]       │ │
│          │  │ └──────────────────┘ │
└──────────┘  └──────────────────────┘
```

### 🔧 Code Structure

```
api/
  └─ WooshApiService.kt       → API Interface
data/
  └─ TicketRepository.kt      → Data layer
viewmodel/
  └─ TicketHistoryViewModel.kt → Logic layer
ui/screens/
  ├─ MainActivity.kt          → Navigation setup
  ├─ TicketHistoryScreen.kt   → Ticket list screen
  └─ BookingScreen.kt         → Booking screen
utils/
  └─ TokenManager.kt          → Token management
```

---

## 📝 Step-by-Step Implementation

### Step 1: Add Dependencies (5 min)
Copy-paste ke `build.gradle`:
```gradle
// Network
implementation 'com.squareup.retrofit2:retrofit:2.9.0'
implementation 'com.squareup.retrofit2:converter-gson:2.9.0'
implementation 'com.squareup.okhttp3:okhttp:4.11.0'
implementation 'com.squareup.okhttp3:logging-interceptor:4.11.0'

// Compose
implementation 'androidx.compose.ui:ui:1.6.0'
implementation 'androidx.compose.material3:material3:1.2.0'

// Navigation
implementation 'androidx.navigation:navigation-compose:2.7.0'

// ViewModel
implementation 'androidx.lifecycle:lifecycle-viewmodel-ktx:2.7.0'
implementation 'androidx.lifecycle:lifecycle-livedata-ktx:2.7.0'

// Coroutines
implementation 'org.jetbrains.kotlinx:kotlinx-coroutines-android:1.7.3'

// Others
implementation 'androidx.security:security-crypto:1.1.0-alpha06'
implementation 'com.google.accompanist:accompanist-swiperefresh:0.32.0'
```

### Step 2: Setup API Client (10 min)
Lihat: `ANDROID_IMPLEMENTATION_GUIDE.md` → Step 2  
Copy code `api/ApiClient.kt` dan `api/WooshApiService.kt`

### Step 3: Create Data Models (5 min)
Salin data classes dari `ANDROID_IMPLEMENTATION_GUIDE.md` Step 1

### Step 4: Create Repository (10 min)
Lihat: `ANDROID_IMPLEMENTATION_GUIDE.md` → Step 3  
Implementasi `TicketRepository.kt`

### Step 5: Create ViewModel (10 min)
Lihat: `ANDROID_IMPLEMENTATION_GUIDE.md` → Step 4  
Implementasi `TicketHistoryViewModel.kt`

### Step 6: Create UI (30 min)
Lihat: `ANDROID_IMPLEMENTATION_GUIDE.md` → Step 5-6  
Implementasi:
- MainActivity dengan BottomNavigationBar
- TicketHistoryScreen dengan filter tabs
- BookingScreen (placeholder)

### Step 7: Test (15 min)
1. Build & run di emulator
2. Login & dapetin token
3. Lihat My Tickets → Should show data
4. Klik filter tabs → Data berubah
5. Pull to refresh → Data refresh

---

## 💡 Important Tips

### Token Management
```kotlin
// Setelah login, simpan token
tokenManager.saveToken(authToken)

// Di API calls, ambil token
val token = tokenManager.getToken()
// Pass ke ApiClient: Bearer $token
```

### Error Handling
```kotlin
// Handle 401 Unauthorized
if (response.code() == 401) {
    // Token expired, logout & redirect to login
    tokenManager.clearToken()
    navigateToLogin()
}

// Handle 5xx Server Error
if (response.code() >= 500) {
    // Show error message
    showError("Server sedang error, coba lagi nanti")
}
```

### Testing Endpoints
Sebelum integrate ke Android, test dulu dengan Postman:
```bash
# Get all tickets
GET http://localhost:8000/api/tickets/history
Headers: Authorization: Bearer YOUR_TOKEN

# Get pending tickets
GET http://localhost:8000/api/tickets/history-filtered?filter=pending
Headers: Authorization: Bearer YOUR_TOKEN
```

### For Emulator
Gunakan base URL: `http://10.0.2.2:8000`  
(10.0.2.2 adalah IP untuk localhost dari emulator)

### For Real Device
Gunakan base URL: `http://<server-ip>:8000`  
(Ganti dengan IP actual server, e.g., 192.168.1.100)

---

## 📊 Response Example

Ketika API dipanggil, response akan seperti ini:
```json
{
  "success": true,
  "message": "Riwayat tiket berhasil diambil",
  "total": 2,
  "data": [
    {
      "booking_id": 1,
      "booking_code": "WOOSH-ABC123",
      "status": "success",
      "schedule": {
        "schedule_id": 5,
        "train_name": "Express Bandung",
        "departure": {
          "station_name": "Halim",
          "time": "2026-06-12 10:00:00"
        },
        "arrival": {
          "station_name": "Tegalluar",
          "time": "2026-06-12 12:00:00"
        },
        "price_per_seat": 150000
      },
      "payment": {
        "payment_id": 1,
        "method": "bank_transfer",
        "status": "success",
        "amount": 300000,
        "date": "2026-06-11 14:30:00"
      },
      "passengers": [
        {
          "name": "Budi Santoso",
          "id_number": "1234567890",
          "seat": "G1-5A"
        }
      ],
      "ticket": {
        "ticket_id": 1,
        "qr_code": "BOARDING-eyJpdiI6IkpQWTZxSVo0...",
        "status": "not_issued"
      },
      "is_completed": true,
      "is_paid": true,
      "is_pending": false
    }
  ],
  "summary": {
    "total_bookings": 2,
    "completed": 1,
    "paid": 1,
    "pending": 1,
    "failed": 0
  }
}
```

Gunakan data ini untuk:
- `is_paid` → Show "Sudah Bayar" badge
- `is_pending` → Show "Bayar Sekarang" button
- `is_completed` → Show "Lihat QR" button
- `passengers` → List penumpang
- `payment.amount` → Show harga total

---

## 🐛 Common Issues

| Issue | Solution |
|-------|----------|
| 401 Unauthorized | Token invalid, login ulang |
| 404 Not Found | Endpoint salah, check base URL |
| Data tidak muncul | Check network logs, verify API |
| UI tidak responsive | Use Coroutines for API calls |
| QR Code null | Pembayaran belum sukses |
| Status tidak update | Pull to refresh atau reload |

---

## 📚 Reference Files

| File | Purpose |
|------|---------|
| `NAVIGATION_MENU_API.md` | Dokumentasi API lengkap |
| `ANDROID_IMPLEMENTATION_GUIDE.md` | Panduan implementasi step-by-step |
| `ANDROID_TASK_CHECKLIST.md` | Task checklist untuk tracking |

---

## ✅ Before You Start

- [ ] Backend API sudah running (`php artisan serve`)
- [ ] Sudah baca ANDROID_IMPLEMENTATION_GUIDE.md
- [ ] Setup Android project dengan Kotlin + Compose
- [ ] Add semua dependencies ke build.gradle
- [ ] Siap untuk mulai coding! 🚀

---

## 🎓 Learning Resources

- Retrofit: https://square.github.io/retrofit/
- Compose: https://developer.android.com/jetpack/compose
- Coroutines: https://developer.android.com/kotlin/coroutines
- StateFlow: https://developer.android.com/kotlin/flow/stateflow-and-sharedflow

---

**Good luck! Jika ada pertanyaan, check dokumentasi atau tanya backend team.** 🎯

Last Updated: 2026-06-12
