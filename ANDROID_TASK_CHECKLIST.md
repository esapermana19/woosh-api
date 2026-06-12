# 📋 Android Development Task Checklist

**Project**: Woosh - Navigation Menu & Ticket History  
**Status**: 🔴 Not Started → 🟡 In Progress → ✅ Completed  
**Target**: Integrate with Backend API  

---

## 📌 Quick Reference

### API Endpoints
```
GET  /api/tickets/history              → Get all ticket history
GET  /api/tickets/history-filtered     → Get filtered tickets (query: filter)
```

### Base URL
- **Emulator**: `http://10.0.2.2:8000`
- **Device**: `http://<server-ip>:8000`

### Required Auth
- **Header**: `Authorization: Bearer {token}`
- **Type**: Bearer Token (from Login)

---

## 🚀 Phase 1: Project Setup (Day 1-2)

### Task 1.1: Add Dependencies
- [ ] Add Retrofit 2.9.0
- [ ] Add OkHttp 4.11.0
- [ ] Add Coroutines 1.7.3
- [ ] Add Jetpack Compose Material3
- [ ] Add Navigation Compose
- [ ] Add Security Crypto
- [ ] Add SwipeRefresh
- [ ] Add Gson Converter
- [ ] Add Glide for images
- [ ] Add ZXing for QR Code (optional)

**File to update**: `build.gradle` (app level)

### Task 1.2: Setup Network Configuration
- [ ] Create `api/ApiClient.kt`
- [ ] Implement Retrofit instance
- [ ] Add OkHttp logging interceptor
- [ ] Add Authorization header interceptor
- [ ] Test connection (try simple GET request)

### Task 1.3: Create API Service
- [ ] Create `api/WooshApiService.kt`
- [ ] Define all data models (TicketItem, PaymentInfo, etc)
- [ ] Create interface with 2 endpoints
- [ ] Add proper Kotlin data classes with @Serializable

**Reference**: See ANDROID_IMPLEMENTATION_GUIDE.md Step 1-2

---

## 🏗️ Phase 2: Architecture Setup (Day 3-4)

### Task 2.1: Create Repository Layer
- [ ] Create `data/TicketRepository.kt`
- [ ] Implement `getTicketHistory()` function
- [ ] Implement `getTicketHistoryFiltered(filter: String)` function
- [ ] Add error handling with Result wrapper
- [ ] Implement token retrieval logic

**Reference**: ANDROID_IMPLEMENTATION_GUIDE.md Step 3

### Task 2.2: Create ViewModel
- [ ] Create `viewmodel/TicketHistoryViewModel.kt`
- [ ] Define `TicketHistoryUiState` data class
- [ ] Implement `loadTicketHistory()` function
- [ ] Implement `filterTickets(filter)` function
- [ ] Implement `refresh()` function
- [ ] Use StateFlow for reactive UI

**Reference**: ANDROID_IMPLEMENTATION_GUIDE.md Step 4

### Task 2.3: Setup Token Manager
- [ ] Create `utils/TokenManager.kt`
- [ ] Implement secure token storage (EncryptedSharedPreferences)
- [ ] Create `saveToken()`, `getToken()`, `clearToken()`
- [ ] Create `isLoggedIn()` check function

**Reference**: ANDROID_IMPLEMENTATION_GUIDE.md Step 8

---

## 🎨 Phase 3: UI Implementation (Day 5-7)

### Task 3.1: Setup Navigation Structure
- [ ] Create `MainActivity.kt` with Scaffold
- [ ] Implement BottomNavigationBar
- [ ] Add 2 menu items: "Home" & "My Tickets"
- [ ] Setup NavHost with 2 routes
- [ ] Implement navigation between screens
- [ ] Test menu switching

**Reference**: ANDROID_IMPLEMENTATION_GUIDE.md Step 5

### Task 3.2: Create Ticket History Screen
- [ ] Create `ui/screens/TicketHistoryScreen.kt`
- [ ] Implement filter tabs (All, Pending, Paid, Failed, Completed)
- [ ] Create `FilterTabs()` composable
- [ ] Create `TicketCard()` composable
- [ ] Create `StatusBadge()` composable
- [ ] Add loading state UI
- [ ] Add error state UI
- [ ] Add empty state UI
- [ ] Implement pull-to-refresh

**Reference**: ANDROID_IMPLEMENTATION_GUIDE.md Step 6

### Task 3.3: Ticket Card Details
- [ ] Display booking code + status badge
- [ ] Display train name
- [ ] Display departure station + time
- [ ] Display arrival station + time
- [ ] Display passenger list
- [ ] Display payment method + amount
- [ ] Display action buttons (Bayar/Lihat QR)

### Task 3.4: Create Booking Screen (Placeholder)
- [ ] Create `ui/screens/BookingScreen.kt`
- [ ] Add button to navigate to schedule search
- [ ] Keep it simple for now (will be filled later)

**Reference**: ANDROID_IMPLEMENTATION_GUIDE.md Step 7

---

## 🔌 Phase 4: Integration & Testing (Day 8-10)

### Task 4.1: Integrate API with ViewModel
- [ ] Test `getTicketHistory()` API call
- [ ] Verify response parsing
- [ ] Handle success response
- [ ] Handle error response
- [ ] Add loading state management
- [ ] Test with real backend data

### Task 4.2: Test All Filters
- [ ] Test filter="all" → show all bookings ✓
- [ ] Test filter="pending" → show unpaid ✓
- [ ] Test filter="paid" → show paid ✓
- [ ] Test filter="failed" → show failed ✓
- [ ] Test filter="completed" → show completed ✓
- [ ] Verify response changes on filter click

### Task 4.3: Test UI Components
- [ ] Verify ticket card displays all info correctly
- [ ] Verify status badge shows correct color
- [ ] Verify pull-to-refresh works
- [ ] Verify loading indicator shows/hides
- [ ] Verify error message displays
- [ ] Verify empty state shows when no data

### Task 4.4: Test Navigation
- [ ] Click "Home" → Go to BookingScreen ✓
- [ ] Click "My Tickets" → Go to TicketHistoryScreen ✓
- [ ] Verify menu stays selected
- [ ] Verify state preserved on back

### Task 4.5: Test Error Scenarios
- [ ] No network connection → Show error
- [ ] Invalid token (401) → Show error, logout?
- [ ] Server error (500) → Show error
- [ ] Empty list → Show empty state
- [ ] Slow network → Show loading spinner

---

## 📲 Phase 5: Polish & Features (Day 11-12)

### Task 5.1: QR Code Display (Optional)
- [ ] Add button "Lihat QR" on paid tickets
- [ ] Implement QR code image display
- [ ] Use ZXing or ML Kit to display QR
- [ ] Add ability to save QR code image

### Task 5.2: Enhance UX
- [ ] Add animation when switching tabs
- [ ] Add animation for list items
- [ ] Improve error messages
- [ ] Add retry button on error
- [ ] Add shimmer loading effect

### Task 5.3: Performance Optimization
- [ ] Implement pagination if needed
- [ ] Add local caching (Room DB)
- [ ] Optimize image loading
- [ ] Profile memory usage
- [ ] Test on low-end devices

### Task 5.4: Offline Support (Optional)
- [ ] Cache ticket data locally
- [ ] Show cached data when offline
- [ ] Sync when back online
- [ ] Add offline indicator

---

## 🧪 Testing Checklist

### Manual Testing (Emulator/Device)
- [ ] App launches without crash
- [ ] Login flow works
- [ ] Token saved correctly
- [ ] Menu navigation works
- [ ] My Tickets screen loads data
- [ ] Filters work correctly
- [ ] Pull-to-refresh works
- [ ] Navigation back/forward works
- [ ] Dark mode support (if applicable)
- [ ] Different screen sizes supported

### Network Testing
- [ ] Test with Wifi
- [ ] Test with Mobile data
- [ ] Test with no network (graceful error)
- [ ] Test with slow network (loading indicator)
- [ ] Test API timeout handling

### Data Validation
- [ ] Empty passenger list handled
- [ ] Null fields handled (ticket_id, qr_code)
- [ ] Large amounts formatted correctly
- [ ] Date/time displayed correctly
- [ ] Long text truncated properly

---

## 🐛 Debugging Guide

### If data doesn't load:
1. Check Logcat for API errors
2. Test endpoint with Postman first
3. Verify token is valid
4. Check base URL (use correct IP)
5. Enable okhttp logging interceptor
6. Check response body in logs

### If UI doesn't update:
1. Verify StateFlow is collected correctly
2. Check ViewModel initialization
3. Verify recomposition is triggered
4. Check for null safety issues
5. Use @Preview for Compose debugging

### If navigation doesn't work:
1. Verify NavHost is setup correctly
2. Check route names match
3. Verify onClick handlers
4. Check backstack state

---

## 📦 Code Organization

```
app/src/main/java/com/example/woosh/
├── api/
│   ├── ApiClient.kt
│   ├── WooshApiService.kt
│   └── models/ (all data classes)
├── data/
│   └── TicketRepository.kt
├── viewmodel/
│   └── TicketHistoryViewModel.kt
├── ui/
│   ├── MainActivity.kt
│   └── screens/
│       ├── TicketHistoryScreen.kt
│       └── BookingScreen.kt
├── utils/
│   └── TokenManager.kt
└── App.kt
```

---

## 📊 Estimated Timeline

| Phase | Tasks | Days | Status |
|-------|-------|------|--------|
| 1 | Setup | 2 | 🔴 |
| 2 | Architecture | 2 | 🔴 |
| 3 | UI | 3 | 🔴 |
| 4 | Integration | 3 | 🔴 |
| 5 | Polish | 2 | 🔴 |
| **Total** | | **12 days** | |

---

## 🎯 Success Criteria

- [ ] App compiles without errors
- [ ] Can login and get valid token
- [ ] My Tickets screen shows data
- [ ] All 5 filters work correctly
- [ ] UI is responsive and looks good
- [ ] Error handling works properly
- [ ] Navigation is smooth
- [ ] Code is clean and well-structured
- [ ] No crashes on normal usage
- [ ] Ready for QA testing

---

## 📞 Communication Protocol

**Backend API Status**: Check NAVIGATION_MENU_API.md  
**For UI Questions**: Check ANDROID_IMPLEMENTATION_GUIDE.md  
**For API Issues**: Contact Backend Team  
**For Android Issues**: Ask me!

---

**Start Date**: 2026-06-12  
**Expected Completion**: 2026-06-24  
**Good luck team! 🚀**
