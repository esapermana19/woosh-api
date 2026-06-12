# 📱 Android Implementation Guide - Navigation Menu & Ticket History

**Backend API Status**: ✅ Ready  
**Date**: 2026-06-12  
**API Base URL**: `http://10.0.2.2:8000/api` (for emulator) or `http://<server-ip>:8000/api` (for device)

---

## 🎯 Task Overview

Membuat fitur navigasi menu yang muncul di semua halaman dengan 2 menu:
1. **Menu Home** → Menampilkan booking activity (schedule search)
2. **Menu My Tickets** → Menampilkan riwayat pemesanan dengan filter status pembayaran

---

## 📋 Pre-requisites

### Android Project Setup
- Minimum SDK: API 24 (Android 7.0)
- Target SDK: API 34+ (Android 14+)
- Kotlin enabled
- AndroidX dependencies

### Required Libraries (Add to `build.gradle` - app level)
```gradle
dependencies {
    // Retrofit & HTTP
    implementation 'com.squareup.retrofit2:retrofit:2.9.0'
    implementation 'com.squareup.retrofit2:converter-gson:2.9.0'
    implementation 'com.squareup.okhttp3:okhttp:4.11.0'
    implementation 'com.squareup.okhttp3:logging-interceptor:4.11.0'
    
    // Jetpack Compose or Material Design 3
    implementation 'androidx.compose.ui:ui:1.6.0'
    implementation 'androidx.compose.material3:material3:1.2.0'
    
    // Navigation
    implementation 'androidx.navigation:navigation-fragment-ktx:2.7.0'
    implementation 'androidx.navigation:navigation-ui-ktx:2.7.0'
    
    // ViewModel & LiveData
    implementation 'androidx.lifecycle:lifecycle-viewmodel-ktx:2.7.0'
    implementation 'androidx.lifecycle:lifecycle-livedata-ktx:2.7.0'
    
    // Room Database (untuk caching lokal)
    implementation 'androidx.room:room-runtime:2.6.0'
    kapt 'androidx.room:room-compiler:2.6.0'
    
    // Coroutines
    implementation 'org.jetbrains.kotlinx:kotlinx-coroutines-android:1.7.3'
    
    // Swipe Refresh
    implementation 'androidx.swiperefreshlayout:swiperefreshlayout:1.1.0'
    
    // QR Code Library (for display)
    implementation 'com.journeyapps:zxing-android-embedded:4.3.0'
    
    // Glide for image loading
    implementation 'com.github.bumptech.glide:glide:4.15.1'
}
```

---

## 🔌 API Service Setup

### Step 1: Buat Retrofit Service Interface
**File**: `app/src/main/java/com/example/woosh/api/WooshApiService.kt`

```kotlin
package com.example.woosh.api

import retrofit2.Response
import retrofit2.http.*

// Data Models
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

// Service Interface
interface WooshApiService {
    
    // Get all ticket history
    @GET("/api/tickets/history")
    suspend fun getTicketHistory(
        @Header("Authorization") token: String
    ): Response<TicketHistoryResponse>
    
    // Get filtered ticket history
    @GET("/api/tickets/history-filtered")
    suspend fun getTicketHistoryFiltered(
        @Header("Authorization") token: String,
        @Query("filter") filter: String = "all"
    ): Response<TicketHistoryResponse>
}
```

### Step 2: Setup Retrofit Client dengan Interceptor
**File**: `app/src/main/java/com/example/woosh/api/ApiClient.kt`

```kotlin
package com.example.woosh.api

import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.concurrent.TimeUnit

object ApiClient {
    private const val BASE_URL = "http://10.0.2.2:8000" // Change for real device
    
    private var token: String = ""
    
    fun setToken(authToken: String) {
        token = authToken
    }
    
    private val httpClient: OkHttpClient
        get() {
            val loggingInterceptor = HttpLoggingInterceptor().apply {
                level = HttpLoggingInterceptor.Level.BODY
            }
            
            return OkHttpClient.Builder()
                .addInterceptor(loggingInterceptor)
                .addInterceptor { chain ->
                    val request = chain.request().newBuilder()
                        .addHeader("Accept", "application/json")
                        .addHeader("Content-Type", "application/json")
                        .apply {
                            if (token.isNotEmpty()) {
                                addHeader("Authorization", "Bearer $token")
                            }
                        }
                        .build()
                    chain.proceed(request)
                }
                .connectTimeout(30, TimeUnit.SECONDS)
                .readTimeout(30, TimeUnit.SECONDS)
                .writeTimeout(30, TimeUnit.SECONDS)
                .build()
        }
    
    val apiService: WooshApiService by lazy {
        Retrofit.Builder()
            .baseUrl(BASE_URL)
            .client(httpClient)
            .addConverterFactory(GsonConverterFactory.create())
            .build()
            .create(WooshApiService::class.java)
    }
}
```

---

## 🏗️ Architecture Setup

### Step 3: Buat Repository
**File**: `app/src/main/java/com/example/woosh/data/TicketRepository.kt`

```kotlin
package com.example.woosh.data

import com.example.woosh.api.ApiClient
import com.example.woosh.api.TicketHistoryResponse
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.withContext

class TicketRepository {
    
    suspend fun getTicketHistory(): Result<TicketHistoryResponse> = withContext(Dispatchers.IO) {
        try {
            val response = ApiClient.apiService.getTicketHistory(
                token = "Bearer ${getAuthToken()}"
            )
            
            if (response.isSuccessful && response.body() != null) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception("API Error: ${response.code()} - ${response.message()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
    
    suspend fun getTicketHistoryFiltered(filter: String): Result<TicketHistoryResponse> = 
        withContext(Dispatchers.IO) {
            try {
                val response = ApiClient.apiService.getTicketHistoryFiltered(
                    token = "Bearer ${getAuthToken()}",
                    filter = filter
                )
                
                if (response.isSuccessful && response.body() != null) {
                    Result.success(response.body()!!)
                } else {
                    Result.failure(Exception("API Error: ${response.code()} - ${response.message()}"))
                }
            } catch (e: Exception) {
                Result.failure(e)
            }
        }
    
    private fun getAuthToken(): String {
        // Ambil dari SharedPreferences atau DataStore
        // return sharedPreferences.getString("auth_token", "") ?: ""
        return "YOUR_AUTH_TOKEN"
    }
}
```

### Step 4: Buat ViewModel
**File**: `app/src/main/java/com/example/woosh/viewmodel/TicketHistoryViewModel.kt`

```kotlin
package com.example.woosh.viewmodel

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.example.woosh.api.TicketHistoryResponse
import com.example.woosh.data.TicketRepository
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.launch

data class TicketHistoryUiState(
    val isLoading: Boolean = false,
    val data: TicketHistoryResponse? = null,
    val error: String? = null,
    val currentFilter: String = "all"
)

class TicketHistoryViewModel : ViewModel() {
    
    private val repository = TicketRepository()
    
    private val _uiState = MutableStateFlow(TicketHistoryUiState())
    val uiState: StateFlow<TicketHistoryUiState> = _uiState
    
    fun loadTicketHistory() {
        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(isLoading = true, error = null)
            
            val result = repository.getTicketHistory()
            result.onSuccess { data ->
                _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    data = data,
                    error = null
                )
            }.onFailure { error ->
                _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    error = error.message ?: "Terjadi kesalahan"
                )
            }
        }
    }
    
    fun filterTickets(filter: String) {
        viewModelScope.launch {
            _uiState.value = _uiState.value.copy(isLoading = true, error = null, currentFilter = filter)
            
            val result = repository.getTicketHistoryFiltered(filter)
            result.onSuccess { data ->
                _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    data = data,
                    error = null
                )
            }.onFailure { error ->
                _uiState.value = _uiState.value.copy(
                    isLoading = false,
                    error = error.message ?: "Gagal memfilter tiket"
                )
            }
        }
    }
    
    fun refresh() {
        if (_uiState.value.currentFilter == "all") {
            loadTicketHistory()
        } else {
            filterTickets(_uiState.value.currentFilter)
        }
    }
}
```

---

## 🎨 UI Implementation

### Step 5: Buat Bottom Navigation Menu
**File**: `app/src/main/java/com/example/woosh/ui/MainActivity.kt`

```kotlin
package com.example.woosh.ui

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.material3.*
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Receipt
import androidx.compose.runtime.*
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.rememberNavController
import com.example.woosh.ui.screens.BookingScreen
import com.example.woosh.ui.screens.TicketHistoryScreen

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent {
            WooshApp()
        }
    }
}

@Composable
fun WooshApp() {
    val navController = rememberNavController()
    var selectedTab by remember { mutableStateOf(0) }
    
    Scaffold(
        bottomBar = {
            NavigationBar {
                NavigationBarItem(
                    icon = { Icon(Icons.Default.Home, contentDescription = "Home") },
                    label = { Text("Home") },
                    selected = selectedTab == 0,
                    onClick = {
                        selectedTab = 0
                        navController.navigate("booking") {
                            popUpTo(navController.graph.startDestinationId) { saveState = true }
                            launchSingleTop = true
                            restoreState = true
                        }
                    }
                )
                NavigationBarItem(
                    icon = { Icon(Icons.Default.Receipt, contentDescription = "My Tickets") },
                    label = { Text("My Tickets") },
                    selected = selectedTab == 1,
                    onClick = {
                        selectedTab = 1
                        navController.navigate("tickets") {
                            popUpTo(navController.graph.startDestinationId) { saveState = true }
                            launchSingleTop = true
                            restoreState = true
                        }
                    }
                )
            }
        }
    ) { paddingValues ->
        NavHost(
            navController = navController,
            startDestination = "booking",
            modifier = Modifier
                .fillMaxSize()
                .padding(paddingValues)
        ) {
            composable("booking") {
                selectedTab = 0
                BookingScreen()
            }
            composable("tickets") {
                selectedTab = 1
                TicketHistoryScreen()
            }
        }
    }
}
```

### Step 6: Buat Ticket History Screen
**File**: `app/src/main/java/com/example/woosh/ui/screens/TicketHistoryScreen.kt`

```kotlin
package com.example.woosh.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.viewmodel.compose.viewModel
import com.example.woosh.api.TicketItem
import com.example.woosh.viewmodel.TicketHistoryViewModel
import com.google.accompanist.swiperefresh.SwipeRefresh
import com.google.accompanist.swiperefresh.rememberSwipeRefreshState

@Composable
fun TicketHistoryScreen(viewModel: TicketHistoryViewModel = viewModel()) {
    val uiState by viewModel.uiState.collectAsState()
    val isRefreshing = uiState.isLoading
    
    LaunchedEffect(Unit) {
        viewModel.loadTicketHistory()
    }
    
    Column(modifier = Modifier.fillMaxSize()) {
        // Filter Tabs
        FilterTabs(
            currentFilter = uiState.currentFilter,
            onFilterChanged = { viewModel.filterTickets(it) }
        )
        
        // Content
        SwipeRefresh(
            state = rememberSwipeRefreshState(isRefreshing),
            onRefresh = { viewModel.refresh() }
        ) {
            when {
                uiState.error != null -> {
                    ErrorScreen(error = uiState.error!!)
                }
                uiState.data == null || uiState.isLoading -> {
                    LoadingScreen()
                }
                uiState.data!!.data.isEmpty() -> {
                    EmptyScreen(filter = uiState.currentFilter)
                }
                else -> {
                    LazyColumn(
                        modifier = Modifier
                            .fillMaxSize()
                            .padding(horizontal = 16.dp, vertical = 8.dp),
                        verticalArrangement = Arrangement.spacedBy(8.dp)
                    ) {
                        items(uiState.data!!.data) { ticket ->
                            TicketCard(ticket = ticket)
                        }
                    }
                }
            }
        }
    }
}

@Composable
fun FilterTabs(currentFilter: String, onFilterChanged: (String) -> Unit) {
    val filters = listOf("all", "pending", "paid", "failed", "completed")
    val filterLabels = mapOf(
        "all" to "Semua",
        "pending" to "Belum Bayar",
        "paid" to "Sudah Bayar",
        "failed" to "Gagal",
        "completed" to "Selesai"
    )
    
    LazyColumn(
        modifier = Modifier
            .fillMaxWidth()
            .horizontalScroll(rememberScrollState())
            .background(Color.White)
            .padding(vertical = 8.dp),
        horizontalArrangement = Arrangement.spacedBy(4.dp),
        contentPadding = PaddingValues(horizontal = 16.dp)
    ) {
        items(filters) { filter ->
            FilterChip(
                selected = currentFilter == filter,
                onClick = { onFilterChanged(filter) },
                label = { Text(filterLabels[filter] ?: filter) }
            )
        }
    }
}

@Composable
fun TicketCard(ticket: TicketItem) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(vertical = 4.dp),
        shape = RoundedCornerShape(12.dp),
        elevation = CardDefaults.cardElevation(defaultElevation = 2.dp)
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(16.dp)
        ) {
            // Header: Booking Code & Status
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text(
                    text = ticket.booking_code,
                    fontWeight = FontWeight.Bold,
                    fontSize = 16.sp
                )
                StatusBadge(ticket.payment.status)
            }
            
            Divider(modifier = Modifier.padding(vertical = 8.dp))
            
            // Train Info
            Text(
                text = ticket.schedule.train_name,
                fontWeight = FontWeight.SemiBold,
                fontSize = 14.sp,
                modifier = Modifier.padding(bottom = 8.dp)
            )
            
            // Departure & Arrival
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(vertical = 8.dp),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Column {
                    Text("Keberangkatan", fontSize = 12.sp, color = Color.Gray)
                    Text(ticket.schedule.departure.station_name, fontWeight = FontWeight.Medium)
                    Text(ticket.schedule.departure.time, fontSize = 11.sp, color = Color.Gray)
                }
                Text("→", fontWeight = FontWeight.Bold)
                Column(horizontalAlignment = Alignment.End) {
                    Text("Tujuan", fontSize = 12.sp, color = Color.Gray)
                    Text(ticket.schedule.arrival.station_name, fontWeight = FontWeight.Medium)
                    Text(ticket.schedule.arrival.time, fontSize = 11.sp, color = Color.Gray)
                }
            }
            
            Divider(modifier = Modifier.padding(vertical = 8.dp))
            
            // Payment & Price Info
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(vertical = 8.dp),
                horizontalArrangement = Arrangement.SpaceBetween
            ) {
                Column {
                    Text("Metode Pembayaran", fontSize = 12.sp, color = Color.Gray)
                    Text(
                        text = ticket.payment.method.replace("_", " ").uppercase(),
                        fontWeight = FontWeight.Medium,
                        fontSize = 13.sp
                    )
                }
                Column(horizontalAlignment = Alignment.End) {
                    Text("Total Harga", fontSize = 12.sp, color = Color.Gray)
                    Text(
                        text = "Rp ${String.format("%,d", ticket.payment.amount)}",
                        fontWeight = FontWeight.Bold,
                        fontSize = 14.sp,
                        color = Color(0xFF00A651)
                    )
                }
            }
            
            // Passengers Info
            if (ticket.passengers.isNotEmpty()) {
                Divider(modifier = Modifier.padding(vertical = 8.dp))
                Text("Penumpang", fontSize = 12.sp, color = Color.Gray, fontWeight = FontWeight.Medium)
                ticket.passengers.forEach { passenger ->
                    Text(
                        text = "${passenger.name} (${passenger.seat})",
                        fontSize = 12.sp,
                        modifier = Modifier.padding(top = 4.dp)
                    )
                }
            }
            
            // Action Buttons
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(top = 12.dp),
                horizontalArrangement = Arrangement.spacedBy(8.dp)
            ) {
                if (ticket.is_paid && ticket.ticket.qr_code != null) {
                    Button(
                        onClick = { /* Show QR Code */ },
                        modifier = Modifier
                            .weight(1f)
                            .height(36.dp),
                        shape = RoundedCornerShape(8.dp)
                    ) {
                        Text("Lihat QR", fontSize = 12.sp)
                    }
                }
                if (ticket.is_pending) {
                    Button(
                        onClick = { /* Go to Payment */ },
                        modifier = Modifier
                            .weight(1f)
                            .height(36.dp),
                        shape = RoundedCornerShape(8.dp)
                    ) {
                        Text("Bayar Sekarang", fontSize = 12.sp)
                    }
                }
            }
        }
    }
}

@Composable
fun StatusBadge(status: String) {
    val (bgColor, textColor, displayText) = when (status) {
        "pending" -> Triple(
            Color(0xFFFFE5CC),
            Color(0xFFE67E22),
            "Menunggu"
        )
        "success" -> Triple(
            Color(0xFFD5F4E6),
            Color(0xFF00A651),
            "Terbayar"
        )
        "failed" -> Triple(
            Color(0xFFFFD5CC),
            Color(0xFFE74C3C),
            "Gagal"
        )
        else -> Triple(Color.LightGray, Color.Gray, "Unknown")
    }
    
    Surface(
        color = bgColor,
        shape = RoundedCornerShape(20.dp)
    ) {
        Text(
            text = displayText,
            color = textColor,
            fontSize = 11.sp,
            fontWeight = FontWeight.SemiBold,
            modifier = Modifier.padding(horizontal = 12.dp, vertical = 4.dp)
        )
    }
}

@Composable
fun LoadingScreen() {
    Box(
        modifier = Modifier.fillMaxSize(),
        contentAlignment = Alignment.Center
    ) {
        CircularProgressIndicator()
    }
}

@Composable
fun ErrorScreen(error: String) {
    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(Color(0xFFFFE5E5)),
        contentAlignment = Alignment.Center
    ) {
        Column(
            horizontalAlignment = Alignment.CenterHorizontally,
            modifier = Modifier.padding(16.dp)
        ) {
            Text("⚠️ Terjadi Kesalahan", fontWeight = FontWeight.Bold, fontSize = 16.sp)
            Text(error, fontSize = 14.sp, modifier = Modifier.padding(top = 8.dp), color = Color.DarkGray)
        }
    }
}

@Composable
fun EmptyScreen(filter: String) {
    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(Color(0xFFF5F5F5)),
        contentAlignment = Alignment.Center
    ) {
        Column(
            horizontalAlignment = Alignment.CenterHorizontally,
            modifier = Modifier.padding(16.dp)
        ) {
            Text("📭 Tidak Ada Data", fontWeight = FontWeight.Bold, fontSize = 16.sp)
            Text("Belum ada tiket dengan filter '$filter'", fontSize = 12.sp, color = Color.Gray, modifier = Modifier.padding(top = 8.dp))
        }
    }
}
```

### Step 7: Buat Booking Screen (placeholder)
**File**: `app/src/main/java/com/example/woosh/ui/screens/BookingScreen.kt`

```kotlin
package com.example.woosh.ui.screens

import androidx.compose.foundation.layout.*
import androidx.compose.material3.Button
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp

@Composable
fun BookingScreen() {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(16.dp),
        verticalArrangement = Arrangement.Center,
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Text("Booking Activity")
        Button(
            onClick = { /* Navigate to Schedule Search */ },
            modifier = Modifier.padding(top = 16.dp)
        ) {
            Text("Cari Jadwal Kereta")
        }
    }
}
```

---

## 🔐 Authentication Setup

### Step 8: Setup Token Management
**File**: `app/src/main/java/com/example/woosh/utils/TokenManager.kt`

```kotlin
package com.example.woosh.utils

import android.content.Context
import androidx.security.crypto.EncryptedSharedPreferences
import androidx.security.crypto.MasterKey

class TokenManager(context: Context) {
    
    private val masterKey = MasterKey.Builder(context)
        .setKeyScheme(MasterKey.KeyScheme.AES256_GCM)
        .build()
    
    private val prefs = EncryptedSharedPreferences.create(
        context,
        "woosh_prefs",
        masterKey,
        EncryptedSharedPreferences.PrefKeyEncryptionScheme.AES256_SIV,
        EncryptedSharedPreferences.PrefValueEncryptionScheme.AES256_GCM
    )
    
    fun saveToken(token: String) {
        prefs.edit().putString("auth_token", token).apply()
        // Also update ApiClient
        // ApiClient.setToken(token)
    }
    
    fun getToken(): String? {
        return prefs.getString("auth_token", null)
    }
    
    fun clearToken() {
        prefs.edit().remove("auth_token").apply()
    }
    
    fun isLoggedIn(): Boolean {
        return !getToken().isNullOrEmpty()
    }
}
```

---

## ✅ Testing Checklist

### Unit Testing
```kotlin
// Test TicketRepository
@Test
fun testGetTicketHistorySuccess() {
    // Mock API response
    // Verify repository returns data correctly
}

@Test
fun testGetTicketHistoryError() {
    // Mock API error
    // Verify error handling
}
```

### UI Testing
```kotlin
// Test TicketHistoryScreen
@Test
fun testFilterTabsClickable() {
    // Verify filter buttons work
}

@Test
fun testTicketCardDisplay() {
    // Verify ticket info displayed correctly
}
```

### Manual Testing Steps
1. ✅ Login ke aplikasi
2. ✅ Buka menu "My Tickets"
3. ✅ Verifikasi tampilan list tiket
4. ✅ Klik filter "Pending" → data berubah
5. ✅ Klik filter "Paid" → data berubah
6. ✅ Pull to refresh → data refresh
7. ✅ Klik "Lihat QR" → tampil QR code
8. ✅ Klik menu "Home" → pindah ke booking screen

---

## 🐛 Common Issues & Solutions

### Issue 1: 401 Unauthorized
**Cause**: Token tidak valid atau expired  
**Solution**: 
- Check token di SharedPreferences
- Implement token refresh mechanism
- Force logout & login ulang

### Issue 2: Data tidak muncul
**Cause**: Networking issue atau endpoint tidak sesuai  
**Solution**:
- Check network logs di Logcat
- Verify base URL (use 10.0.2.2 for emulator, actual IP for device)
- Test endpoint dengan Postman terlebih dahulu

### Issue 3: UI tidak responsive
**Cause**: Main thread blocking  
**Solution**:
- Pastikan API calls di background thread (Coroutines)
- Use StateFlow untuk UI updates
- Add loading indicator

### Issue 4: QR Code tidak muncul
**Cause**: Ticket belum diterbitkan atau qr_code field null  
**Solution**:
- Check payment status di response
- Verify backend sudah generate QR code setelah pembayaran
- Add null-check di UI

---

## 📚 Dependencies untuk build.gradle

```gradle
dependencies {
    // Core
    implementation 'androidx.core:core-ktx:1.12.0'
    
    // Lifecycle
    implementation 'androidx.lifecycle:lifecycle-runtime-ktx:2.7.0'
    implementation 'androidx.lifecycle:lifecycle-viewmodel-ktx:2.7.0'
    
    // Compose
    implementation platform('androidx.compose:compose-bom:2024.01.00')
    implementation 'androidx.compose.ui:ui'
    implementation 'androidx.compose.ui:ui-graphics'
    implementation 'androidx.compose.ui:ui-tooling-preview'
    implementation 'androidx.compose.material3:material3'
    implementation 'androidx.activity:activity-compose:1.8.0'
    
    // Navigation
    implementation 'androidx.navigation:navigation-compose:2.7.0'
    
    // Networking
    implementation 'com.squareup.retrofit2:retrofit:2.9.0'
    implementation 'com.squareup.retrofit2:converter-gson:2.9.0'
    implementation 'com.squareup.okhttp3:okhttp:4.11.0'
    implementation 'com.squareup.okhttp3:logging-interceptor:4.11.0'
    
    // Coroutines
    implementation 'org.jetbrains.kotlinx:kotlinx-coroutines-android:1.7.3'
    
    // Security
    implementation 'androidx.security:security-crypto:1.1.0-alpha06'
    
    // Refresh
    implementation 'com.google.accompanist:accompanist-swiperefresh:0.32.0'
    
    // Testing
    testImplementation 'junit:junit:4.13.2'
    androidTestImplementation 'androidx.test.espresso:espresso-core:3.5.1'
}
```

---

## 🚀 Deployment Steps

1. **Test di Emulator**
   - Setup API base URL ke 10.0.2.2:8000
   - Run app dan test semua flow

2. **Test di Device**
   - Update base URL ke IP server (e.g., 192.168.x.x:8000)
   - Connect device ke same network
   - Test offline scenario

3. **Production Setup**
   - Update base URL ke production server
   - Add certificate pinning untuk HTTPS
   - Implement proper error handling
   - Enable ProGuard/R8 obfuscation

---

## 📞 Support & Contact

**Backend Developer**: Check NAVIGATION_MENU_API.md  
**API Status**: http://<server>:8000/api/tickets/history  
**Last Updated**: 2026-06-12

Jika ada pertanyaan atau masalah, hubungi backend team! 🎯
