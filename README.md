# HealthFirst - Login & Selection System

A modern, responsive health application with user authentication and pathway selection.

## Features

### 🔐 Login Page (Page 1)
- **Modern UI**: Clean card-based design with Poppins/Open Sans fonts
- **Responsive**: Mobile-first design that works on all devices
- **Secure Authentication**: PHP session management with password hashing
- **Error Handling**: User-friendly error messages

### 🎯 Selection Page (Page 2)
- **Two Pathways**: Medical Condition vs General Improvement
- **Interactive Cards**: Hover effects and smooth animations
- **Session Management**: Saves user selection to database
- **Accessibility**: Keyboard navigation and proper ARIA support

### 📋 Multi-Step Quiz (Pages 3a-3g)
- **7-Step Assessment**: Personal info, medical conditions, medications, sleep, diet, stress/mood, lifestyle
- **Smooth Navigation**: Next/Back buttons with progress tracking
- **Form Validation**: Client-side validation with error messages
- **Interactive Elements**: Toggle switches, sliders, multi-select buttons
- **Data Persistence**: Saves all quiz data to normalized database tables
- **Responsive Design**: Mobile-optimized with smooth animations

## Setup Instructions

### 1. Database Setup
1. Start XAMPP (Apache + MySQL)
2. Open phpMyAdmin (http://localhost/phpmyadmin)
3. Import the SQL file: `sql/setup.sql`
4. This creates the `healthfirst` database with all required tables

### 2. Test Credentials
- **Username**: `testuser`
- **Password**: `password123`

### 3. File Structure
```
healthfirst/
├── index.php              # Login page
├── dashboard.php           # Selection page
├── quiz.php               # Multi-step health assessment
├── logout.php             # Logout handler
├── register.php           # Registration placeholder
├── forgot-password.php    # Password recovery placeholder
├── config/
│   └── database.php       # Database connection
├── assets/
│   ├── css/
│   │   └── style.css      # All styles (login, dashboard, quiz)
│   └── js/
│       ├── login.js       # Login validation
│       ├── dashboard.js   # Selection handling
│       └── quiz.js        # Quiz navigation & validation
└── sql/
    └── setup.sql          # Database schema
```

## Design System

### Colors
- **Primary Blue**: #2E86C1 (buttons, headings)
- **Background**: #F4F6F7 (light gray)
- **Medical Accent**: #E74C3C (red)
- **Lifestyle Accent**: #28B463 (green)

### Typography
- **Headings**: Poppins (600-700 weight)
- **Body**: Open Sans (300-600 weight)

### Components
- **Cards**: White background, rounded corners, subtle shadows
- **Buttons**: Full-width, rounded, hover effects
- **Forms**: Clean inputs with focus states

## Testing Checklist

### Authentication Flow
- [ ] Valid login redirects to dashboard
- [ ] Invalid credentials show error message
- [ ] Empty fields trigger validation
- [ ] Session management prevents unauthorized access
- [ ] Logout functionality works

### Selection & Navigation
- [ ] Selection cards are clickable and redirect to quiz
- [ ] User focus is saved to database
- [ ] Responsive design works on mobile/desktop

### Quiz Functionality
- [ ] All 7 steps navigate smoothly with Next/Back buttons
- [ ] Progress bar updates correctly
- [ ] Form validation prevents invalid submissions
- [ ] Required fields show error messages
- [ ] Multi-select buttons work (medical conditions, lifestyle)
- [ ] Toggle switches function properly (diet preferences)
- [ ] Sliders update values in real-time (stress/mood)
- [ ] Final submission saves all data to database
- [ ] Quiz completion redirects to dashboard with success message

## Browser Support
- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

## Security Features
- Password hashing with `password_verify()`
- SQL injection prevention with prepared statements
- XSS protection with `htmlspecialchars()`
- Session-based authentication
- Input sanitization and validation

## Next Steps
1. Implement full registration system
2. Add password recovery functionality
3. Build medical condition forms (Pages 3a-3d)
4. Build lifestyle improvement forms (Pages 3e-3g)
5. Add email verification
6. Implement password strength requirements
