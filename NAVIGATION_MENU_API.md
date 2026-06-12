# API Documentation - Navigation Menu & Ticket History

## Overview
Backend API untuk fitur navigasi menu yang muncul di semua halaman aplikasi Android Woosh. Menu ini memiliki 2 fitur utama:
1. **Menu Home**: Menampilkan halaman booking activity
2. **Menu My Ticket**: Menampilkan riwayat pemesanan tiket dengan berbagai status

---

## Endpoint 1: Get Ticket History (All)
### Deskripsi
Mengambil semua riwayat pemesanan tiket milik user yang sedang login, dengan informasi lengkap pembayaran, jadwal, dan data penumpang.

### URL
```
GET /api/tickets/history
```

### Authentication
**Required**: Bearer Token (User harus login)

### Headers
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

### Response Success (200 OK)
```json
{
  "success": true,
  "message": "Riwayat tiket berhasil diambil",
  "total": 2,
  "data": [
    {
      "booking_id": 1,
      "booking_code": "WOOSH-ABC123XY",
      "status": "success",
      "schedule": {
        "schedule_id": 5,
        "train_name": "Express Bandung",
        "departure": {
          "station_name": "Stasiun Halim",
          "time": "2026-06-12 10:00:00"
        },
        "arrival": {
          "station_name": "Stasiun Tegalluar",
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
        },
        {
          "name": "Siti Nurhaliza",
          "id_number": "0987654321",
          "seat": "G1-5B"
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
    },
    {
      "booking_id": 2,
      "booking_code": "WOOSH-XYZ789AB",
      "status": "pending",
      "schedule": {
        "schedule_id": 8,
        "train_name": "Premium Night",
        "departure": {
          "station_name": "Stasiun Karawang",
          "time": "2026-06-13 20:00:00"
        },
        "arrival": {
          "station_name": "Stasiun Padalarang",
          "time": "2026-06-13 22:00:00"
        },
        "price_per_seat": 200000
      },
      "payment": {
        "payment_id": 2,
        "method": "ewallet",
        "status": "pending",
        "amount": 200000,
        "date": null
      },
      "passengers": [
        {
          "name": "Ahmad Wijaya",
          "id_number": "9876543210",
          "seat": "G2-3C"
        }
      ],
      "ticket": {
        "ticket_id": null,
        "qr_code": null,
        "status": "not_issued"
      },
      "is_completed": false,
      "is_paid": false,
      "is_pending": true
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

### Response Error
```json
{
  "message": "Unauthenticated",
  "status": 401
}
```

---

## Endpoint 2: Get Ticket History (Filtered)
### Deskripsi
Mengambil riwayat pemesanan tiket dengan filter berdasarkan status pembayaran atau status pemesanan.

### URL
```
GET /api/tickets/history-filtered?filter=pending
```

### Authentication
**Required**: Bearer Token (User harus login)

### Query Parameters
| Parameter | Type   | Values | Description |
|-----------|--------|--------|-------------|
| filter    | string | all, pending, paid, failed, completed | Filter berdasarkan status. Default: all |

### Headers
```
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

### Request Examples

#### Get All Bookings (Default)
```
GET /api/tickets/history-filtered?filter=all
```

#### Get Pending Payments Only
```
GET /api/tickets/history-filtered?filter=pending
```

#### Get Paid Bookings Only
```
GET /api/tickets/history-filtered?filter=paid
```

#### Get Failed Bookings
```
GET /api/tickets/history-filtered?filter=failed
```

#### Get Completed Bookings
```
GET /api/tickets/history-filtered?filter=completed
```

### Response Success (200 OK)
```json
{
  "success": true,
  "message": "Riwayat tiket dengan filter 'pending' berhasil diambil",
  "filter": "pending",
  "total": 1,
  "data": [
    {
      "booking_id": 2,
      "booking_code": "WOOSH-XYZ789AB",
      "status": "pending",
      "schedule": {
        "schedule_id": 8,
        "train_name": "Premium Night",
        "departure": {
          "station_name": "Stasiun Karawang",
          "time": "2026-06-13 20:00:00"
        },
        "arrival": {
          "station_name": "Stasiun Padalarang",
          "time": "2026-06-13 22:00:00"
        },
        "price_per_seat": 200000
      },
      "payment": {
        "payment_id": 2,
        "method": "ewallet",
        "status": "pending",
        "amount": 200000,
        "date": null
      },
      "passengers": [
        {
          "name": "Ahmad Wijaya",
          "id_number": "9876543210",
          "seat": "G2-3C"
        }
      ],
      "ticket": {
        "ticket_id": null,
        "qr_code": null,
        "status": "not_issued"
      },
      "is_completed": false,
      "is_paid": false,
      "is_pending": true
    }
  ]
}
```

---

## Status Field Explanation

### Booking Status (status)
- **pending**: Booking dibuat, menunggu pembayaran
- **success**: Pembayaran berhasil, booking confirmed
- **failed**: Pembayaran gagal atau kedaluwarsa

### Payment Status (payment.status)
- **pending**: Menunggu pembayaran
- **success**: Pembayaran berhasil
- **failed**: Pembayaran gagal

### Ticket Status (ticket.status)
- **not_issued**: Tiket belum diterbitkan (menunggu pembayaran sukses)
- **issued**: Tiket sudah diterbitkan
- **used**: Tiket sudah digunakan saat boarding
- **cancelled**: Tiket dibatalkan

---

## Data Structure Mapping

### Android UI Components

#### Menu Home (Booking Activity)
- Menampilkan: Daftar jadwal kereta untuk booking baru
- Data dari: `/api/schedules/search` (existing endpoint)

#### Menu My Ticket (Ticket History)
- Menggunakan: `/api/tickets/history` atau `/api/tickets/history-filtered`
- Display fields:
  - Booking code, Train name, Status badge
  - Departure & Arrival stations dengan waktu
  - Passengers list
  - Payment status & amount
  - QR Code (jika sudah issued)
  - Action buttons (sesuaikan berdasarkan status)

### Filter Logic for Android UI
```
Filter tabs yang bisa ditampilkan:
- All (show semua bookings) → GET /api/tickets/history?filter=all
- Pending (belum bayar) → GET /api/tickets/history?filter=pending
- Paid (sudah bayar) → GET /api/tickets/history?filter=paid
- Completed (selesai) → GET /api/tickets/history?filter=completed
- Failed (gagal) → GET /api/tickets/history?filter=failed
```

---

## Implementation Notes for Android Team

### 1. HTTP Client Setup
```kotlin
// Use Retrofit with interceptor untuk menambah Bearer token
val client = OkHttpClient.Builder()
    .addInterceptor { chain ->
        val request = chain.request().newBuilder()
            .addHeader("Authorization", "Bearer $token")
            .addHeader("Accept", "application/json")
            .build()
        chain.proceed(request)
    }
    .build()
```

### 2. Data Models (Kotlin)
```kotlin
data class TicketHistoryResponse(
    val success: Boolean,
    val message: String,
    val total: Int,
    val data: List<TicketItem>,
    val summary: SummaryData
)

data class TicketItem(
    val booking_id: Int,
    val booking_code: String,
    val status: String,
    val schedule: ScheduleInfo,
    val payment: PaymentInfo,
    val passengers: List<PassengerInfo>,
    val ticket: TicketInfo,
    val is_completed: Boolean,
    val is_paid: Boolean,
    val is_pending: Boolean
)

data class ScheduleInfo(
    val schedule_id: Int,
    val train_name: String,
    val departure: StationInfo,
    val arrival: StationInfo,
    val price_per_seat: Int
)

data class StationInfo(
    val station_name: String,
    val time: String
)

data class PaymentInfo(
    val payment_id: Int?,
    val method: String,
    val status: String,
    val amount: Int,
    val date: String?
)

data class PassengerInfo(
    val name: String,
    val id_number: String,
    val seat: String
)

data class TicketInfo(
    val ticket_id: Int?,
    val qr_code: String?,
    val status: String
)

data class SummaryData(
    val total_bookings: Int,
    val completed: Int,
    val paid: Int,
    val pending: Int,
    val failed: Int
)
```

### 3. API Service (Retrofit)
```kotlin
interface WooshApiService {
    @GET("/api/tickets/history")
    suspend fun getTicketHistory(): TicketHistoryResponse

    @GET("/api/tickets/history-filtered")
    suspend fun getTicketHistoryFiltered(
        @Query("filter") filter: String
    ): TicketHistoryResponse
}
```

### 4. UI Implementation Tips
- **Tab Navigation**: Gunakan TabLayout dengan 5 tab (All, Pending, Paid, Completed, Failed)
- **Pull-to-Refresh**: Implementasi SwipeRefreshLayout untuk refresh data
- **Sorting**: Data sudah disort (terbaru di atas) dari backend
- **Status Indicators**: Gunakan badge/chip untuk menampilkan status dengan warna berbeda
- **Empty State**: Tampilkan pesan jika tidak ada booking pada filter tertentu
- **QR Code Display**: Gunakan library seperti ZXing atau ML Kit untuk display/scan QR code

---

## Database Relations

Berikut struktur tabel yang digunakan:

```
users
├── user_id (PK)
├── full_name
├── email
├── phone
└── password_hash

bookings
├── booking_id (PK)
├── user_id (FK → users)
├── schedule_id (FK → schedules)
├── booking_code
├── status (pending|success|failed)
└── created_at (implicit)

payments
├── payment_id (PK)
├── booking_id (FK → bookings)
├── payment_method
├── payment_status
├── amount
└── payment_date

schedules
├── schedule_id (PK)
├── train_id (FK → trains)
├── departure_station (FK → stations)
├── arrival_station (FK → stations)
├── departure_time
├── arrival_time
└── price

trains
├── train_id (PK)
└── train_name

stations
├── station_id (PK)
└── station_name

booking_passengers
├── passenger_id (PK)
├── booking_id (FK → bookings)
├── full_name
├── id_number
└── seat_number

tickets
├── ticket_id (PK)
├── booking_id (FK → bookings)
├── qr_code
└── status
```

---

## Error Handling

### 401 Unauthorized
```json
{
  "message": "Unauthenticated",
  "status": 401
}
```
**Cause**: Token tidak valid atau expired  
**Solution**: User harus login ulang

### 422 Validation Error
```json
{
  "message": "Validation Error",
  "status": 422,
  "errors": {
    "filter": ["Filter harus salah satu dari: all, pending, paid, failed, completed"]
  }
}
```

### 500 Server Error
```json
{
  "success": false,
  "message": "Gagal memproses request",
  "error": "Error message details"
}
```

---

## Testing with Curl

### Get All Ticket History
```bash
curl -X GET "http://localhost:8000/api/tickets/history" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Get Pending Tickets
```bash
curl -X GET "http://localhost:8000/api/tickets/history-filtered?filter=pending" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Get Paid Tickets
```bash
curl -X GET "http://localhost:8000/api/tickets/history-filtered?filter=paid" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

---

## Future Enhancements

1. **Pagination**: Tambah limit/offset untuk performa dengan data banyak
2. **Search**: Implementasi search berdasarkan booking code, station name, etc
3. **Date Filtering**: Filter berdasarkan tanggal keberangkatan
4. **Sorting Options**: Sort berdasarkan tanggal, harga, status, dll
5. **Export**: Export tiket dalam format PDF atau image
6. **Notifications**: Push notification untuk perubahan status pembayaran
7. **Rebook**: API endpoint untuk rebooking/reschedule jadwal

---

## Last Updated
**Date**: 2026-06-12  
**Version**: 1.0  
**Status**: Ready for Android Integration
