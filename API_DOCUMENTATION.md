# Professional Presence API Documentation

## Overview
A comprehensive Laravel-based presence management system with JWT authentication, role-based access control, and real-time presence tracking.

## Base URL
```
http://localhost:8000/api
```

## Authentication
This API uses JWT (JSON Web Tokens) for authentication. Include the token in the Authorization header:
```
Authorization: Bearer {your-jwt-token}
```

## Response Format
All API responses follow a consistent JSON format:
```json
{
    "status": "success|error",
    "message": "Response message",
    "data": {}
}
```

## Error Codes
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `429` - Too Many Requests
- `500` - Internal Server Error

---

## Authentication Endpoints

### Register User
**POST** `/auth/register`

Register a new user account.

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "super_admin|admin_company|employee",
    "company_id": 1,
    "division_id": 1,
    "employee_id": "EMP001",
    "phone": "+1234567890",
    "plan_id": 1,
    "company_name": "Acme Corp"
}
```

**Parameters:**
- `name` (required): User's full name (2-100 characters)
- `email` (required): Valid email address (max 100 characters, must be unique)
- `password` (required): Password (minimum 6 characters)
- `password_confirmation` (required): Password confirmation (must match password)
- `role` (required): User role (super_admin, admin_company, or employee)
- `company_id` (optional): Existing company ID
- `division_id` (optional): Division ID within company
- `employee_id` (optional): Employee identifier (max 50 characters)
- `phone` (optional): Phone number (max 20 characters)
- `plan_id` (optional): Plan ID for new companies
- `company_name` (optional): Company name for new company creation (max 100 characters)

**Response:**
```json
{
    "status": "success",
    "message": "User successfully registered",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "role": "employee",
            "company_id": 1,
            "division_id": 1,
            "employee_id": "EMP001",
            "phone": "+1234567890",
            "is_active": true
        },
        "company": {
            "id": 1,
            "name": "Acme Corp",
            "email": "john@example.com",
            "plan_id": 1,
            "is_active": true
        },
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
    }
}
```

### Login
**POST** `/auth/login`

Authenticate user and receive JWT token.

**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

**Response:**
```json
{
    "status": "success",
    "message": "Login successful",
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "bearer",
        "expires_in": 3600,
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "role": "employee",
            "company_id": 1,
            "division_id": 1,
            "employee_id": "EMP001",
            "phone": "+1234567890",
            "is_active": true,
            "company": {
                "id": 1,
                "name": "Acme Corp",
                "plan_id": 1
            },
            "division": {
                "id": 1,
                "name": "Engineering",
                "company_id": 1
            }
        }
    }
}
```

### Refresh Token
**POST** `/auth/refresh`

Refresh the JWT token.

**Headers:**
```
Authorization: Bearer {current-token}
```

**Response:**
```json
{
    "status": "success",
    "message": "Token refreshed successfully",
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "expires_in": 3600
    }
}
```

### Logout
**POST** `/auth/logout`

Invalidate the current JWT token.

**Headers:**
```
Authorization: Bearer {your-token}
```

**Response:**
```json
{
    "status": "success",
    "message": "Successfully logged out"
}
```

### Get User Profile
**GET** `/auth/user-profile`

Retrieve the authenticated user's profile information.

**Headers:**
```
Authorization: Bearer {your-token}
```

**Response:**
```json
{
    "status": "success",
    "message": "User profile retrieved successfully",
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "role": "employee",
        "company_id": 1,
        "division_id": 1,
        "employee_id": "EMP001",
        "phone": "+1234567890",
        "is_active": true,
        "company": {
            "id": 1,
            "name": "Acme Corp",
            "plan_id": 1
        },
        "division": {
            "id": 1,
            "name": "Engineering",
            "company_id": 1
        }
    }
}
```

---

## Presence Management

### Check-in
**POST** `/presence/checkin`

Record employee check-in.

**Headers:**
```
Authorization: Bearer {your-token}
```

**Request Body:**
```json
{
    "latitude": -6.2088,
    "longitude": 106.8456,
    "photo": "base64_encoded_image_string",
    "notes": "Working from office today"
}
```

**Parameters:**
- `latitude` (required): GPS latitude coordinate
- `longitude` (required): GPS longitude coordinate  
- `photo` (optional): Base64 encoded image for check-in verification
- `notes` (optional): Additional notes for the check-in

**Response:**
```json
{
    "status": "success",
    "message": "Check-in recorded successfully",
    "data": {
        "id": 1,
        "user_id": 1,
        "company_id": 1,
        "division_id": 1,
        "checkin_time": "08:00:00",
        "checkin_latitude": -6.2088,
        "checkin_longitude": 106.8456,
        "checkin_photo": "checkin_photos/photo_123.jpg",
        "checkin_notes": "Working from office today",
        "is_late": false,
        "late_duration": null,
        "date": "2024-01-15",
        "created_at": "2024-01-15T08:00:00.000000Z"
    }
}
```

### Check-out
**POST** `/presence/checkout`

Record employee check-out.

**Headers:**
```
Authorization: Bearer {your-token}
```

**Request Body:**
```json
{
    "latitude": -6.2088,
    "longitude": 106.8456,
    "photo": "base64_encoded_image_string",
    "notes": "Finished work for today"
}
```

**Parameters:**
- `latitude` (required): GPS latitude coordinate
- `longitude` (required): GPS longitude coordinate
- `photo` (optional): Base64 encoded image for check-out verification
- `notes` (optional): Additional notes for the check-out

**Response:**
```json
{
    "status": "success",
    "message": "Check-out recorded successfully",
    "data": {
        "id": 1,
        "user_id": 1,
        "company_id": 1,
        "division_id": 1,
        "checkin_time": "08:00:00",
        "checkout_time": "17:00:00",
        "checkout_latitude": -6.2088,
        "checkout_longitude": 106.8456,
        "checkout_photo": "checkout_photos/photo_124.jpg",
        "checkout_notes": "Finished work for today",
        "is_early_checkout": false,
        "early_checkout_duration": null,
        "work_duration": "09:00:00",
        "date": "2024-01-15",
        "updated_at": "2024-01-15T17:00:00.000000Z"
    }
}
```

### Get Presence Status
**GET** `/presence/status`

Get current presence status for the authenticated user.

**Headers:**
```
Authorization: Bearer {your-token}
```

**Response:**
```json
{
    "status": "success",
    "message": "Presence status retrieved successfully",
    "data": {
        "today_checkin": {
            "id": 1,
            "user_id": 1,
            "checkin_time": "08:00:00",
            "checkin_latitude": -6.2088,
            "checkin_longitude": 106.8456,
            "is_late": false,
            "date": "2024-01-15"
        },
        "today_checkout": null,
        "config": {
            "checkin_start": "08:00:00",
            "checkin_end": "09:00:00",
            "checkout_start": "17:00:00",
            "checkout_end": "18:00:00"
        },
        "can_checkin": false,
        "can_checkout": true,
        "is_checked_in": true,
        "is_checked_out": false
    }
}
```

### Get Presence History
**GET** `/presence/history`

Retrieve presence history for the authenticated user or company (admin only).

**Headers:**
```
Authorization: Bearer {your-token}
```

**Query Parameters:**
- `company_id` (optional, admin only): Filter by company ID
- `user_id` (optional, admin only): Filter by specific user ID
- `start_date` (optional): Start date for filtering (YYYY-MM-DD)
- `end_date` (optional): End date for filtering (YYYY-MM-DD)
- `type` (optional): Filter by presence type
- `page` (optional): Page number for pagination
- `per_page` (optional): Items per page (default: 15)

**Response:**
```json
{
    "status": "success",
    "message": "Presence history retrieved successfully",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "user_id": 1,
                "company_id": 1,
                "division_id": 1,
                "date": "2024-01-15",
                "checkin_time": "08:00:00",
                "checkout_time": "17:00:00",
                "checkin_latitude": -6.2088,
                "checkin_longitude": 106.8456,
                "checkout_latitude": -6.2088,
                "checkout_longitude": 106.8456,
                "checkin_photo": "checkin_photos/photo_123.jpg",
                "checkout_photo": "checkout_photos/photo_124.jpg",
                "checkin_notes": "Working from office",
                "checkout_notes": "Finished work",
                "is_late": false,
                "late_duration": null,
                "is_early_checkout": false,
                "early_checkout_duration": null,
                "work_duration": "09:00:00",
                "created_at": "2024-01-15T08:00:00.000000Z",
                "updated_at": "2024-01-15T17:00:00.000000Z",
                "user": {
                    "id": 1,
                    "name": "John Doe",
                    "email": "john@example.com",
                    "employee_id": "EMP001"
                }
            }
        ],
        "first_page_url": "http://localhost:8000/api/presence/history?page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "http://localhost:8000/api/presence/history?page=1",
        "next_page_url": null,
        "path": "http://localhost:8000/api/presence/history",
        "per_page": 15,
        "prev_page_url": null,
        "to": 1,
        "total": 1
    }
}
```

### Get Today's Presence Status
**GET** `/presence/today`

Get current day's presence status.

**Headers:**
```
Authorization: Bearer {your-token}
```

**Response:**
```json
{
    "status": "success",
    "message": "Today's presence retrieved successfully",
    "data": {
        "id": 1,
        "user_id": 1,
        "company_id": 1,
        "division_id": 1,
        "date": "2024-01-15",
        "checkin_time": "08:00:00",
        "checkout_time": "17:00:00",
        "checkin_latitude": -6.2088,
        "checkin_longitude": 106.8456,
        "checkout_latitude": -6.2088,
        "checkout_longitude": 106.8456,
        "checkin_photo": "checkin_photos/photo_123.jpg",
        "checkout_photo": "checkout_photos/photo_124.jpg",
        "checkin_notes": "Working from office",
        "checkout_notes": "Finished work",
        "is_late": false,
        "late_duration": null,
        "is_early_checkout": false,
        "early_checkout_duration": null,
        "work_duration": "09:00:00",
        "created_at": "2024-01-15T08:00:00.000000Z",
        "updated_at": "2024-01-15T17:00:00.000000Z"
    }
}
```

### Get Company Presence History (Admin Only)
**GET** `/presence/company-history`

Retrieve company-wide presence history (Admin Company role required).

**Headers:**
```
Authorization: Bearer {admin-token}
```

**Query Parameters:**
- `company_id` (optional): Filter by company ID (super admin only)
- `user_id` (optional): Filter by specific user ID
- `start_date` (optional): Start date for filtering (YYYY-MM-DD)
- `end_date` (optional): End date for filtering (YYYY-MM-DD)
- `type` (optional): Filter by presence type
- `page` (optional): Page number for pagination
- `per_page` (optional): Items per page (default: 15)

**Response:**
```json
{
    "status": "success",
    "message": "Company presence history retrieved successfully",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "user_id": 1,
                "company_id": 1,
                "division_id": 1,
                "date": "2024-01-15",
                "checkin_time": "08:00:00",
                "checkout_time": "17:00:00",
                "checkin_latitude": -6.2088,
                "checkin_longitude": 106.8456,
                "checkout_latitude": -6.2088,
                "checkout_longitude": 106.8456,
                "checkin_photo": "checkin_photos/photo_123.jpg",
                "checkout_photo": "checkout_photos/photo_124.jpg",
                "checkin_notes": "Working from office",
                "checkout_notes": "Finished work",
                "is_late": false,
                "late_duration": null,
                "is_early_checkout": false,
                "early_checkout_duration": null,
                "work_duration": "09:00:00",
                "created_at": "2024-01-15T08:00:00.000000Z",
                "updated_at": "2024-01-15T17:00:00.000000Z",
                "user": {
                    "id": 1,
                    "name": "John Doe",
                    "email": "john@example.com",
                    "employee_id": "EMP001",
                    "division": {
                        "id": 1,
                        "name": "Engineering"
                    }
                }
            }
        ],
        "first_page_url": "http://localhost:8000/api/presence/company-history?page=1",
        "from": 1,
        "last_page": 7,
        "last_page_url": "http://localhost:8000/api/presence/company-history?page=7",
        "next_page_url": "http://localhost:8000/api/presence/company-history?page=2",
        "path": "http://localhost:8000/api/presence/company-history",
        "per_page": 15,
        "prev_page_url": null,
        "to": 15,
        "total": 100
    }
}
```

---

## Manual Presence Requests

### Create Manual Presence Request
**POST** `/manual-presence-requests`

Create a manual presence request (for sick leave, etc.).

**Headers:**
```
Authorization: Bearer {your-token}
```

**Request Body:**
```json
{
    "date": "2024-01-15",
    "type": "sick",
    "reason": "Flu symptoms, unable to come to office",
    "checkin_time": "08:30:00",
    "checkout_time": "17:30:00"
}
```

**Parameters:**
- `date` (required): Date for the manual presence request (YYYY-MM-DD)
- `type` (required): Type of request (sick, leave, etc.)
- `reason` (required): Reason for the manual presence request
- `checkin_time` (required): Proposed check-in time (HH:MM:SS)
- `checkout_time` (required): Proposed check-out time (HH:MM:SS)

**Response:**
```json
{
    "status": "success",
    "message": "Manual presence request created successfully",
    "data": {
        "id": 1,
        "user_id": 1,
        "company_id": 1,
        "division_id": 1,
        "date": "2024-01-15",
        "type": "sick",
        "reason": "Flu symptoms, unable to come to office",
        "checkin_time": "08:30:00",
        "checkout_time": "17:30:00",
        "status": "pending",
        "approved_by": null,
        "approved_at": null,
        "admin_notes": null,
        "created_at": "2024-01-14T15:30:00.000000Z",
        "updated_at": "2024-01-14T15:30:00.000000Z"
    }
}
```

### List Manual Presence Requests
**GET** `/manual-presence-requests`

Retrieve manual presence requests (filtered by company for admins, own requests for employees).

**Headers:**
```
Authorization: Bearer {your-token}
```

**Query Parameters:**
- `company_id` (optional, super admin only): Filter by company ID
- `status` (optional): Filter by status (pending, approved, rejected)
- `page` (optional): Page number for pagination
- `per_page` (optional): Items per page (default: 15)

**Response:**
```json
{
    "status": "success",
    "message": "Manual presence requests retrieved successfully",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "user_id": 1,
                "company_id": 1,
                "division_id": 1,
                "date": "2024-01-15",
                "type": "sick",
                "reason": "Flu symptoms, unable to come to office",
                "checkin_time": "08:30:00",
                "checkout_time": "17:30:00",
                "status": "pending",
                "approved_by": null,
                "approved_at": null,
                "admin_notes": null,
                "created_at": "2024-01-14T15:30:00.000000Z",
                "updated_at": "2024-01-14T15:30:00.000000Z",
                "user": {
                    "id": 1,
                    "name": "John Doe",
                    "email": "john@example.com",
                    "employee_id": "EMP001"
                }
            }
        ],
        "first_page_url": "http://localhost:8000/api/manual-presence-requests?page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "http://localhost:8000/api/manual-presence-requests?page=1",
        "next_page_url": null,
        "path": "http://localhost:8000/api/manual-presence-requests",
        "per_page": 15,
        "prev_page_url": null,
        "to": 1,
        "total": 1
    }
}
```

### Approve Manual Presence Request (Admin Only)
**PUT** `/manual-presence-requests/{id}/approve`

Approve a manual presence request.

**Headers:**
```
Authorization: Bearer {admin-token}
```

**Request Body (optional):**
```json
{
    "admin_notes": "Approved with medical certificate"
}
```

**Response:**
```json
{
    "status": "success",
    "message": "Manual presence request approved successfully",
    "data": {
        "id": 1,
        "user_id": 1,
        "company_id": 1,
        "division_id": 1,
        "date": "2024-01-15",
        "type": "sick",
        "reason": "Flu symptoms, unable to come to office",
        "checkin_time": "08:30:00",
        "checkout_time": "17:30:00",
        "status": "approved",
        "approved_by": 2,
        "approved_at": "2024-01-14T16:00:00.000000Z",
        "admin_notes": "Approved with medical certificate",
        "created_at": "2024-01-14T15:30:00.000000Z",
        "updated_at": "2024-01-14T16:00:00.000000Z"
    }
}
```

### Reject Manual Presence Request (Admin Only)
**PUT** `/manual-presence-requests/{id}/reject`

Reject a manual presence request.

**Headers:**
```
Authorization: Bearer {admin-token}
```

**Request Body (optional):**
```json
{
    "admin_notes": "Please provide medical certificate"
}
```

**Response:**
```json
{
    "status": "success",
    "message": "Manual presence request rejected successfully",
    "data": {
        "id": 1,
        "user_id": 1,
        "company_id": 1,
        "division_id": 1,
        "date": "2024-01-15",
        "type": "sick",
        "reason": "Flu symptoms, unable to come to office",
        "checkin_time": "08:30:00",
        "checkout_time": "17:30:00",
        "status": "rejected",
        "approved_by": 2,
        "approved_at": "2024-01-14T16:00:00.000000Z",
        "admin_notes": "Please provide medical certificate",
        "created_at": "2024-01-14T15:30:00.000000Z",
        "updated_at": "2024-01-14T16:00:00.000000Z"
    }
}
```



---

## Plans Management

### List Plans
**GET** `/plans`

Retrieve all active plans (accessible to all authenticated users).

**Headers:**
```
Authorization: Bearer {your-token}
```

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Basic Plan",
            "description": "Basic features for small teams",
            "price": 29.99,
            "employee_limit": 50,
            "features": {
                "presence_tracking": true,
                "manual_requests": true,
                "reports": "basic"
            },
            "is_active": true,
            "created_at": "2024-01-01T00:00:00.000000Z",
            "updated_at": "2024-01-01T00:00:00.000000Z"
        }
    ],
    "message": "Plans retrieved successfully"
}
```

### Create Plan (Super Admin Only)
**POST** `/plans`

Create a new subscription plan.

**Headers:**
```
Authorization: Bearer {super-admin-token}
```

**Request Body:**
```json
{
    "name": "Premium Plan",
    "description": "Advanced features for large organizations",
    "price": 99.99,
    "employee_limit": 500,
    "features": {
        "presence_tracking": true,
        "manual_requests": true,
        "reports": "advanced",
        "analytics": true
    }
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "name": "Premium Plan",
        "description": "Advanced features for large organizations",
        "price": 99.99,
        "employee_limit": 500,
        "features": {
            "presence_tracking": true,
            "manual_requests": true,
            "reports": "advanced",
            "analytics": true
        },
        "is_active": true,
        "created_at": "2024-01-15T10:00:00.000000Z",
        "updated_at": "2024-01-15T10:00:00.000000Z"
    },
    "message": "Plan created successfully"
}
```

### Update Plan (Super Admin Only)
**PUT** `/plans/{id}`

Update an existing plan.

**Headers:**
```
Authorization: Bearer {super-admin-token}
```

**Request Body:**
```json
{
    "name": "Premium Plan Updated",
    "price": 89.99,
    "employee_limit": 1000
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "name": "Premium Plan Updated",
        "description": "Advanced features for large organizations",
        "price": 89.99,
        "employee_limit": 1000,
        "features": {
            "presence_tracking": true,
            "manual_requests": true,
            "reports": "advanced",
            "analytics": true
        },
        "is_active": true,
        "created_at": "2024-01-15T10:00:00.000000Z",
        "updated_at": "2024-01-15T11:00:00.000000Z"
    },
    "message": "Plan updated successfully"
}
```

### Toggle Plan Status (Super Admin Only)
**PATCH** `/plans/{id}/toggle-status`

Activate or deactivate a plan.

**Headers:**
```
Authorization: Bearer {super-admin-token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "name": "Premium Plan Updated",
        "is_active": false,
        "updated_at": "2024-01-15T12:00:00.000000Z"
    },
    "message": "Plan status updated successfully"
}
```

---

## Companies Management

### List Companies (Super Admin Only)
**GET** `/companies`

Retrieve all companies with pagination.

**Headers:**
```
Authorization: Bearer {super-admin-token}
```

**Query Parameters:**
- `page` (optional): Page number for pagination
- `per_page` (optional): Items per page (default: 15)

**Response:**
```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "name": "Acme Corporation",
                "email": "admin@acme.com",
                "phone": "+1234567890",
                "address": "123 Business St, City, State",
                "plan_id": 1,
                "is_active": true,
                "created_at": "2024-01-01T00:00:00.000000Z",
                "updated_at": "2024-01-01T00:00:00.000000Z",
                "plan": {
                    "id": 1,
                    "name": "Basic Plan",
                    "employee_limit": 50
                },
                "divisions": [
                    {
                        "id": 1,
                        "name": "Engineering",
                        "company_id": 1
                    }
                ]
            }
        ],
        "first_page_url": "http://localhost:8000/api/companies?page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "http://localhost:8000/api/companies?page=1",
        "next_page_url": null,
        "path": "http://localhost:8000/api/companies",
        "per_page": 15,
        "prev_page_url": null,
        "to": 1,
        "total": 1
    }
}
```

### Create Company (Super Admin Only)
**POST** `/companies`

Create a new company.

**Headers:**
```
Authorization: Bearer {super-admin-token}
```

**Request Body:**
```json
{
    "name": "Tech Innovations Inc",
    "email": "admin@techinnovations.com",
    "phone": "+1987654321",
    "address": "456 Innovation Ave, Tech City",
    "plan_id": 2
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "name": "Tech Innovations Inc",
        "email": "admin@techinnovations.com",
        "phone": "+1987654321",
        "address": "456 Innovation Ave, Tech City",
        "plan_id": 2,
        "is_active": true,
        "created_at": "2024-01-15T10:00:00.000000Z",
        "updated_at": "2024-01-15T10:00:00.000000Z"
    },
    "message": "Company created successfully"
}
```

### Update Company (Super Admin Only)
**PUT** `/companies/{id}`

Update company information.

**Headers:**
```
Authorization: Bearer {super-admin-token}
```

**Request Body:**
```json
{
    "name": "Tech Innovations Corporation",
    "phone": "+1987654322",
    "address": "789 New Innovation Blvd, Tech City"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "name": "Tech Innovations Corporation",
        "email": "admin@techinnovations.com",
        "phone": "+1987654322",
        "address": "789 New Innovation Blvd, Tech City",
        "plan_id": 2,
        "is_active": true,
        "created_at": "2024-01-15T10:00:00.000000Z",
        "updated_at": "2024-01-15T11:00:00.000000Z"
    },
    "message": "Company updated successfully"
}
```

### Toggle Company Status (Super Admin Only)
**PATCH** `/companies/{id}/toggle-status`

Activate or deactivate a company.

**Headers:**
```
Authorization: Bearer {super-admin-token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "name": "Tech Innovations Corporation",
        "is_active": false,
        "updated_at": "2024-01-15T12:00:00.000000Z"
    },
    "message": "Company status updated successfully"
}
```

---

## Rate Limiting

The API implements rate limiting to prevent abuse:
- **General endpoints**: 60 requests per minute
- **Authentication endpoints**: 5 requests per minute
- **Presence endpoints**: 30 requests per minute

When rate limit is exceeded, you'll receive a `429 Too Many Requests` response:
```json
{
    "status": "error",
    "message": "Too many requests. Please try again later."
}
```

---

## User Roles & Permissions

### Super Admin
- Full system access
- Manage all users, companies, and plans
- Access global reports and analytics

### Admin Company
- Manage company employees
- Approve/reject manual presence requests
- Access company-wide presence reports
- Configure presence settings

### Employee
- Check in/out
- View personal presence history
- Create manual presence requests
- Update own profile

---

## Data Types

### Presence Types
- `1` - RFID
- `2` - Face Recognition
- `3` - Fingerprint

### Manual Presence Types
- `sick` - Sick leave
- `leave` - Personal leave
- `business` - Business trip
- `remote` - Remote work

### Request Status
- `pending` - Awaiting approval
- `approved` - Approved by admin
- `rejected` - Rejected by admin

---

## Error Handling

The API returns detailed error messages for debugging:

**Validation Error (422):**
```json
{
    "status": "error",
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."]
    }
}
```

**Authentication Error (401):**
```json
{
    "status": "error",
    "message": "Unauthenticated."
}
```

**Authorization Error (403):**
```json
{
    "status": "error",
    "message": "This action is unauthorized."
}
```

---

## Testing

The API includes comprehensive test coverage:
- 41 test cases
- 282 assertions
- Authentication flow testing
- Role-based access control testing
- Presence management testing
- Manual presence workflow testing
- Error handling validation

Run tests with:
```bash
php artisan test
```

---

## Environment Configuration

Key environment variables:
```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=presence_api
DB_USERNAME=root
DB_PASSWORD=

# JWT
JWT_SECRET=your-secret-key
JWT_TTL=60

# Storage
FILESYSTEM_DISK=local
# For S3: FILESYSTEM_DISK=s3
# AWS_ACCESS_KEY_ID=your-key
# AWS_SECRET_ACCESS_KEY=your-secret
# AWS_DEFAULT_REGION=us-east-1
# AWS_BUCKET=your-bucket
```

---

## Support

For technical support or questions about this API, please refer to the project documentation or contact the development team.

**Version:** 1.0.0  
**Last Updated:** January 2024  
**Framework:** Laravel 11.x  
**PHP Version:** 8.2+