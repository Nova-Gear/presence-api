# Professional Presence Management API

A comprehensive Laravel-based presence management system with JWT authentication, role-based access control, and real-time presence tracking.

## 🚀 Features

- **JWT Authentication** - Secure token-based authentication
- **Role-Based Access Control** - Super Admin, Admin Company, and Employee roles
- **Real-time Presence Tracking** - Check-in/check-out with GPS coordinates
- **Manual Presence Requests** - Sick leave, remote work approval workflow
- **Multi-device Support** - RFID, Face Recognition, Fingerprint integration
- **Rate Limiting** - API protection against abuse
- **Comprehensive Testing** - 41 test cases with 282 assertions
- **Professional Documentation** - Complete API documentation
- **Configurable Storage** - Local or AWS S3 support

## 🛠️ Tech Stack

- **Backend**: Laravel 11.x
- **Database**: MySQL/SQLite
- **Authentication**: JWT (tymon/jwt-auth)
- **Testing**: PHPUnit
- **PHP Version**: 8.2+

## 📋 Requirements

- PHP 8.2 or higher
- Composer
- MySQL 8.0+ or SQLite
- Node.js & NPM (for frontend assets)

## 🔧 Installation

### 1. Clone the Repository
```bash
git clone <repository-url>
cd simple-presence-api
```

### 2. Install Dependencies
```bash
composer install
npm install
```

### 3. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

### 4. Database Configuration
Update your `.env` file with database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=presence_api
DB_USERNAME=root
DB_PASSWORD=your_password
```

For SQLite (development):
```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite
```

### 5. Database Migration & Seeding
```bash
php artisan migrate
php artisan db:seed
```

### 6. Storage Setup
```bash
php artisan storage:link
```

### 7. Start Development Server
```bash
php artisan serve
```

The API will be available at `http://localhost:8000`

## 🧪 Testing

Run the complete test suite:
```bash
php artisan test
```

Run specific test categories:
```bash
# Authentication tests
php artisan test --filter AuthControllerTest

# Presence management tests
php artisan test --filter PresenceControllerTest

# Manual presence tests
php artisan test --filter ManualPresenceControllerTest
```

## 📚 API Documentation

Detailed API documentation is available in [`API_DOCUMENTATION.md`](./API_DOCUMENTATION.md)

### Quick Start Examples

#### Register a New User
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "employee",
    "plan_id": 1,
    "company_name": "Acme Corp"
  }'
```

#### Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "password": "password123"
  }'
```

#### Check In/Out
```bash
curl -X POST http://localhost:8000/api/presence/checkin-checkout \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "latitude": -6.2088,
    "longitude": 106.8456,
    "type": 1,
    "data": "RFID_TAG_123"
  }'
```

## 🏗️ Project Structure

```
simple-presence-api/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php
│   │   │   ├── PresenceController.php
│   │   │   └── ManualPresenceRequestController.php
│   │   └── Middleware/
│   │       ├── RoleMiddleware.php
│   │       ├── RateLimitMiddleware.php
│   │       └── LoggingMiddleware.php
│   └── Models/
│       ├── User.php
│       ├── Company.php
│       ├── Division.php
│       ├── Plan.php
│       ├── Presence.php
│       └── ManualPresenceRequest.php
├── database/
│   ├── migrations/
│   └── seeders/
├── tests/
│   ├── Feature/
│   └── Unit/
├── API_DOCUMENTATION.md
└── README.md
```

## 👥 User Roles & Permissions

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
- Check in/out with location tracking
- View personal presence history
- Create manual presence requests
- Update own profile

## 🔒 Security Features

- **JWT Authentication** with configurable expiration
- **Rate Limiting** to prevent API abuse
- **Role-based Authorization** middleware
- **Input Validation** and sanitization
- **Password Hashing** using Laravel's bcrypt
- **CORS Protection** for cross-origin requests
- **Request Logging** for audit trails

## 📊 Database Schema

### Core Tables
- `users` - User accounts with roles
- `companies` - Company information and settings
- `divisions` - Company divisions/departments
- `plans` - Subscription plans with limits
- `presences` - Check-in/check-out records
- `manual_presence_requests` - Leave/sick day requests

### Key Relationships
- Users belong to Companies
- Companies have Plans
- Users can have multiple Presences
- Manual requests require admin approval

## 🚦 Rate Limiting

- **Authentication endpoints**: 5 requests/minute
- **Presence endpoints**: 30 requests/minute
- **General endpoints**: 60 requests/minute

## 📝 Logging

The application logs all API requests and responses:
- Request details (method, URL, headers, body)
- Response status and duration
- User information (if authenticated)
- Error tracking and debugging information

Logs are stored in `storage/logs/laravel.log`

## 🌐 Environment Configuration

### Development
```env
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=sqlite
```

### Production
```env
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
FILESYSTEM_DISK=s3
```

### AWS S3 Configuration (Optional)
```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
```

## 🔄 API Versioning

The API uses URL-based versioning:
- Current version: `v1`
- Base URL: `http://localhost:8000/api/`

## 📈 Performance Optimization

- **Database Indexing** on frequently queried columns
- **Eager Loading** to prevent N+1 queries
- **Response Caching** for static data
- **Pagination** for large datasets
- **Rate Limiting** to prevent server overload

## 🐛 Troubleshooting

### Common Issues

1. **JWT Token Issues**
   ```bash
   php artisan jwt:secret
   php artisan config:clear
   ```

2. **Database Connection**
   - Check `.env` database credentials
   - Ensure database server is running
   - Run `php artisan migrate:status`

3. **Permission Errors**
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

4. **Clear Application Cache**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan route:clear
   ```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines

- Follow PSR-12 coding standards
- Write tests for new features
- Update documentation for API changes
- Ensure all tests pass before submitting PR

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:
- Check the [API Documentation](./API_DOCUMENTATION.md)
- Review the test cases for usage examples
- Check Laravel logs in `storage/logs/laravel.log`

## 🎯 Roadmap

- [ ] Mobile app integration
- [ ] Advanced reporting dashboard
- [ ] Geofencing for location-based check-ins
- [ ] Integration with HR systems
- [ ] Multi-language support
- [ ] Advanced analytics and insights

---

**Version:** 1.0.0  
**Last Updated:** January 2024  
**Maintained by:** Development Team
