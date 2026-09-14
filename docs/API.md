# Digital Marketing SaaS — API Documentation

## Overview

The Digital Marketing SaaS API provides RESTful endpoints for managing social media campaigns, clients, invoices, workflows, and more.

- **Base URL:** `https://your-domain.com/api/v1`
- **Authentication:** Bearer token (Laravel Sanctum)
- **Content-Type:** `application/json`

## Authentication

### Login
```
POST /api/v1/login
Content-Type: application/json

{
    "email": "<EMAIL>",
    "password": "password123"
}

Response 200:
{
    "token": "1|laravel_sanctum_token...",
    "user": { ... }
}
```

### Logout
```
POST /api/v1/logout
Authorization: Bearer {token}
```

### Get Current User
```
GET /api/v1/user
Authorization: Bearer {token}
```

## Error Responses

All errors follow a consistent format:

```json
{
    "message": "Human-readable error description",
    "error": "error_code",
    "errors": { ... }  // Validation errors only (422)
}
```

### Error Codes

| HTTP | Error Code | Description |
|------|------------|-------------|
| 401 | `authentication_required` | Missing or invalid token |
| 403 | `forbidden` | Insufficient permissions |
| 404 | `not_found` | Resource not found |
| 405 | `method_not_allowed` | HTTP method not supported |
| 422 | `validation_error` | Request validation failed |
| 429 | `rate_limit_exceeded` | Too many requests |
| 500 | `internal_error` | Unexpected server error |

## Resources

### Social Posts

#### List Posts
```
GET /api/v1/posts?status=draft&platform=twitter&page=1&per_page=20
Authorization: Bearer {token}
```

#### Create Post
```
POST /api/v1/posts
Authorization: Bearer {token}

{
    "content": "Hello World!",
    "platform": "twitter",
    "social_account_id": 1,
    "status": "draft",
    "scheduled_at": "2026-09-20T10:00:00Z"
}
```

#### Get Single Post
```
GET /api/v1/posts/{post}
Authorization: Bearer {token}
```

#### Update Post
```
PUT /api/v1/posts/{post}
Authorization: Bearer {token}

{
    "content": "Updated content"
}
```

#### Delete Post
```
DELETE /api/v1/posts/{post}
Authorization: Bearer {token}
```

### Campaigns

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/campaigns` | List campaigns |
| POST | `/api/v1/campaigns` | Create campaign |
| GET | `/api/v1/campaigns/{campaign}` | Get single campaign |
| PUT | `/api/v1/campaigns/{campaign}` | Update campaign |
| DELETE | `/api/v1/campaigns/{campaign}` | Delete campaign |

### Clients

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/clients` | List clients |
| POST | `/api/v1/clients` | Create client |
| GET | `/api/v1/clients/{client}` | Get single client |
| PUT | `/api/v1/clients/{client}` | Update client |
| DELETE | `/api/v1/clients/{client}` | Delete client |

### Invoices

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/invoices` | List invoices |
| POST | `/api/v1/invoices` | Create invoice |
| GET | `/api/v1/invoices/{invoice}` | Get single invoice |
| PUT | `/api/v1/invoices/{invoice}` | Update invoice |
| DELETE | `/api/v1/invoices/{invoice}` | Delete invoice |

### Workflows

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/workflows` | List workflows |
| POST | `/api/v1/workflows` | Create workflow |
| GET | `/api/v1/workflows/{workflow}` | Get single workflow |
| PUT | `/api/v1/workflows/{workflow}` | Update workflow |
| DELETE | `/api/v1/workflows/{workflow}` | Delete workflow |

## Rate Limiting

API endpoints are rate-limited to **60 requests per minute** per authenticated user. When exceeded, a `429 Too Many Requests` response is returned with a `Retry-After` header.

## Pagination

List endpoints return paginated results:

```json
{
    "data": [...],
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100
}
```

The `per_page` parameter accepts values between 1 and 100. Values outside this range will be clamped.
