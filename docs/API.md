# 🚗 DriveMaster API Documentation

**Version:** 1.0  
**Authentication:** Bearer Token (Laravel Sanctum)

---

## 📚 Table of Contents

1. [Authentication](#authentication)
2. [User Roles](#user-roles)
3. [Mutations](#mutations)
4. [Queries](#queries)
5. [Types](#types)
6. [Enums](#enums)
7. [Error Handling](#error-handling)
8. [Rate Limiting](#rate-limiting)
9. [Examples](#examples)

---

## Authentication

### Endpoints

| Schema | Endpoint | Auth Required |
|--------|----------|---------------|
| `auth` | `/graphql` | ❌ No |
| `default` | `/graphql` | ✅ Yes |

### Login
```graphql
mutation {
  login(email: "student@example.com", password: "password") {
    token
    user {
      id
      name
      email
      role
    }
  }
}
```

**Response:**
```json
{
  "data": {
    "login": {
      "token": "1|abc123...",
      "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "student"
      }
    }
  }
}
```

### Register Student
```graphql
mutation {
  registerStudent(
    name: "John Doe"
    email: "john@example.com"
    password: "password"
    password_confirmation: "password"
  ) {
    token
    user {
      id
      name
      email
      role
    }
  }
}
```

## Register Instructor
```graphql
mutation {
  registerInstructor(
    name: "Jane Smith"
    email: "jane@example.com"
    password: "password123"
    password_confirmation: "password123"
    phone: "+48123456789"
    bio: "Experienced driving instructor"
    experience_years: 10
    car_model: "Toyota Corolla"
  ) {
    token
    user {
      id
      name
      email
      role
      is_approved
      profile {
        phone
        bio
        experience_years
        car_model
      }
    }
  }
}
```

### Using Token

Add to HTTP headers:
```
Authorization: Bearer 1|abc123...
```

---

## User Roles

| Role | Description | Permissions |
|------|-------------|-------------|
| `admin` | Administrator | Approve/reject instructors, view reports, all access |
| `instructor` | Driving instructor | Confirm/cancel lessons, view schedule |
| `student` | Student | Book/cancel lessons, view available instructors |

---

## Mutations

### 📅 Lesson Management

#### bookLesson
**Role:** `student`  
**Description:** Book a lesson with an instructor
```graphql
mutation {
  bookLesson(
    instructor_id: 1
    date: "2025-12-15"
    slot: 1  # 1-6 (8:00-10:00, 10:00-12:00, etc.)
    notes: "First lesson, please bring teaching materials"
  ) {
    id
    status
    start_time
    end_time
    instructor {
      id
      name
    }
    student {
      id
      name
    }
  }
}
```

**Slots:**
- `1`: 08:00 - 10:00
- `2`: 10:00 - 12:00
- `3`: 12:00 - 14:00
- `4`: 14:00 - 16:00
- `5`: 16:00 - 18:00
- `6`: 18:00 - 20:00

**Errors:**
- `Instructor not found or not approved`
- `Invalid slot number. Must be between 1 and 6`
- `This time slot is already booked`
- `Cannot book lessons in the past`

---

#### confirmLesson
**Role:** `instructor`  
**Description:** Confirm a planned lesson
```graphql
mutation {
  confirmLesson(lesson_id: 123) {
    id
    status
    instructor {
      name
    }
    student {
      name
    }
  }
}
```

**Errors:**
- `You can only confirm your own lessons`
- `You can confirm only planned lessons`

---


#### cancelLesson
**Role:** `student`, `instructor`  
**Description:** Cancel a lesson
```graphql
mutation {
  cancelLesson(
    lesson_id: 123
    reason: "Personal emergency"
  ) {
    id
    status
    cancel_reason
    cancelled_by
  }
}
```

**Rules:**
- **Students:** Must cancel at least 12 hours before lesson
- **Instructors:** Can cancel anytime

**Errors:**
- `You can only cancel your own lessons`
- `Students must cancel lesson at least 12 hours in advance`
- `Lesson with status 'completed' cannot be cancelled`

---

### 👨‍🏫 Instructor Management

#### approveInstructor
**Role:** `admin`  
**Description:** Approve pending instructor
```graphql
mutation {
  approveInstructor(instructor_id: 5) {
    id
    name
    email
    is_approved
  }
}
```

**Errors:**
- `Only admins can perform this action`
- `Instructor not found`
- `Instructor is already approved`

---

#### rejectInstructor
**Role:** `admin`  
**Description:** Reject instructor application
```graphql
mutation {
  rejectInstructor(
    instructor_id: 5
    reason: "Insufficient experience"
  ) {
    id
    name
    profile {
      rejection_reason
    }
  }
}
```

**Note:** Rejected instructors are soft-deleted

**Errors:**
- `Only admins can perform this action`
- `Cannot reject approved instructor`

---

### 📊 Reports

#### generateReport
**Role:** `admin`  
**Description:** Generate PDF report (async job)
```graphql
mutation {
  generateReport(
    report_type: "weekly"  # daily, weekly, monthly, custom
    date_from: "2025-12-01"
    date_to: "2025-12-07"
  ) {
    message
    estimated_time
    report_type
    date_from
    date_to
  }
}
```

**Response:**
```json
{
  "data": {
    "generateReport": {
      "message": "Report generation started. You will receive an email with download link.",
      "estimated_time": "5-10 minutes",
      "report_type": "weekly",
      "date_from": "2025-12-01",
      "date_to": "2025-12-07"
    }
  }
}
```

---

## Queries

### 👨‍🏫 Instructors

#### availableInstructors
**Role:** Any authenticated user  
**Description:** Get list of approved instructors
```graphql
query {
  availableInstructors {
    id
    name
    email
    user_avatar
    profile {
      phone
      bio
      experience_years
      car_model
    }
  }
}
```

**Cache:** 10 minutes

---

#### pendingInstructors
**Role:** `admin`  
**Description:** Get list of pending instructors
```graphql
query {
  pendingInstructors {
    id
    name
    email
    created_at
    profile {
      phone
      bio
      experience_years
      car_model
    }
  }
}
```

**Note:** NOT cached (always fresh data)

---
### 📅 Lessons

#### availableSlots
**Role:** Any authenticated user  
**Description:** Get available time slots for instructor on specific date
```graphql
query {
  availableSlots(
    instructor_id: 1
    date: "2025-12-15"
  ) {
    start_time
    end_time
  }
}
```

**Response:**
```json
{
  "data": {
    "availableSlots": [
      {
        "start_time": "2025-12-15 08:00:00",
        "end_time": "2025-12-15 10:00:00"
      },
      {
        "start_time": "2025-12-15 12:00:00",
        "end_time": "2025-12-15 14:00:00"
      }
    ]
  }
}
```

**Cache:** 5 minutes

---

#### myLessons
**Role:** `student`, `instructor`  
**Description:** Get user's lessons
```graphql
query {
  myLessons {
    id
    start_time
    end_time
    status
    notes
    instructor {
      id
      name
      user_avatar
    }
    student {
      id
      name
    }
  }
}
```

**Returns:**
- For **students**: lessons where they are the student
- For **instructors**: lessons where they are the instructor

---

#### instructorSchedule
**Role:** `instructor`, `admin`  
**Description:** Get instructor's full schedule
```graphql
query {
  instructorSchedule(
    instructor_id: 1  # Optional for instructors (defaults to self)
    date_from: "2025-12-01"
    date_to: "2025-12-31"
    lesson_status: confirmed  # Optional filter
  ) {
    id
    start_time
    end_time
    status
    instructor {
      name
    }
    student {
      name
    }
  }
}
```

**Rules:**
- **Instructors:** Can only view their own schedule (instructor_id ignored)
- **Admins:** Must provide instructor_id

**Cache:** 10 minutes

---

#### me
**Role:** Any authenticated user  
**Description:** Get current user info
```graphql
query {
  me {
    id
    name
    email
    role
    is_approved
    profile {
      phone
      bio
    }
  }
}
```

---

## Types

### User
```graphql
type User {
  id: Int!
  name: String!
  email: String!
  role: UserRoleEnum!
  is_approved: Boolean!
  user_avatar: String
  profile: Profile
  created_at: String!
  updated_at: String!
}
```

### Profile
```graphql
type Profile {
  id: Int!
  user_id: Int!
  phone: String!
  bio: String
  experience_years: Int
  car_model: String
  rejection_reason: String
}
```

### Lesson
```graphql
type Lesson {
  id: Int!
  instructor_id: Int!
  student_id: Int!
  start_time: String!
  end_time: String!
  status: LessonStatusEnum!
  notes: String
  cancelled_by: Int
  cancel_reason: String
  instructor: User!
  student: User!
  created_at: String!
  updated_at: String!
}
```

### Slot
```graphql
type Slot {
  start_time: String!
  end_time: String!
}
```

### AuthPayload
```graphql
type AuthPayload {
  token: String!
  user: User!
}
```

### ReportResponse
```graphql
type ReportResponse {
  message: String!
  estimated_time: String!
  report_type: String!
  date_from: String!
  date_to: String!
}
```

---

## Enums

### UserRoleEnum
```graphql
enum UserRoleEnum {
  admin
  instructor
  student
}
```

### LessonStatusEnum
```graphql
enum LessonStatusEnum {
  planned
  confirmed
  completed
  cancelled
}
```

---
## Rate Limiting

To prevent abuse, API requests are rate-limited per user (authenticated) or IP address (unauthenticated).

### Limits

| Schema | Endpoint | Limit | Window | Applied By |
|--------|----------|-------|--------|------------|
| `auth` (login/register) | `/graphql` | 10 requests | 1 minute | IP address |
| `default` (all other queries) | `/graphql` | 60 requests | 1 minute | User ID or IP |

### Rate Limit Headers

Every response includes rate limit information:
```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
Retry-After: 60
```

### Exceeded Limit Response

When limit is exceeded, you'll receive a `429 Too Many Requests` response:
```json
{
  "message": "Too Many Attempts.",
  "exception": "Illuminate\\Http\\Exceptions\\ThrottleRequestsException"
}
```

**Wait for `Retry-After` seconds before making another request.**

### Best Practices

✅ **Cache responses** on client-side when possible  
✅ **Batch queries** instead of multiple single requests  
✅ **Monitor headers** to avoid hitting limits  
✅ **Implement exponential backoff** if you hit 429

### Configuration

Rate limits are configured in:
- **`app/Providers/RouteServiceProvider.php`** - Limiter definitions
- **`config/graphql.php`** - Middleware assignment

To modify limits, update `RouteServiceProvider.php`:
```php
RateLimiter::for('graphql', function (Request $request) {
    return Limit::perMinute(100)  // Change to 100 requests/minute
        ->by($request->user()?->id ?: $request->ip());
});
```
---

## Examples

### Complete Booking Flow

#### 1. Student Login
```graphql
mutation {
  login(email: "student@example.com", password: "password") {
    token
  }
}
```

#### 2. Browse Instructors
```graphql
query {
  availableInstructors {
    id
    name
    profile {
      experience_years
      car_model
    }
  }
}
```

#### 3. Check Available Slots
```graphql
query {
  availableSlots(instructor_id: 1, date: "2025-12-15") {
    start_time
    end_time
  }
}
```

#### 4. Book Lesson
```graphql
mutation {
  bookLesson(
    instructor_id: 1
    date: "2025-12-15"
    slot: 1
    notes: "First lesson"
  ) {
    id
    status
  }
}
```

#### 5. Instructor Confirms
```graphql
mutation {
  confirmLesson(lesson_id: 123) {
    id
    status
  }
}
```

---

### Admin Workflow

#### 1. View Pending Instructors
```graphql
query {
  pendingInstructors {
    id
    name
    email
    profile {
      experience_years
      bio
    }
  }
}
```

#### 2. Approve Instructor
```graphql
mutation {
  approveInstructor(instructor_id: 5) {
    id
    is_approved
  }
}
```

#### 3. Generate Weekly Report
```graphql
mutation {
  generateReport(
    report_type: "weekly"
    date_from: "2025-12-01"
    date_to: "2025-12-07"
  ) {
    message
  }
}
```

---


