# UniContent
# CMS for SUSU System Programming Department

**Simple content management system** designed for non-technical staff and professors to manage department website content effortlessly.
## 🌟 Key Features

- 📝 Intuitive content management interface
- 👥 Faculty profile management system
- 📰 News publication platform
- 📚 Scientific publications catalog
- 🔐 Simple authentication system
- 🚀 No database required (file-based storage)

## 📂 Project Structure

```bash
UniContent/
├── _images/                  # System images
├── _styles/                  # System styles
├── images/                   # Main images
├── UniContent/               # Core application directory
│   ├── _images/              # Internal images
│   ├── _styles/              # Internal styles
│   ├── output/               # Generated news output files
│   │   ├── _images/
│   │   ├── _styles/
│   │   └── uploads/
│   ├── profiles_html/        # Faculty profile HTML files
│   │   ├── _styles/
│   │   ├── images/
│   │   └── uploads/profiles/
│   ├── auth.php              # Authentication system
│   ├── create_profile.php    # Profile creation
│   ├── edit_profile.php      # Profile editing
│   ├── index.php             # Main portal page
│   └── ...                   # Additional components
```
## 🛠 Core Modules
## 👨‍🏫 Profile Management System
profiles.php - Display all faculty profiles

create_profile.php - Create new faculty profiles

profile_functions.php - Profile creation logic

edit_profile.php - Edit existing profiles

edit_profile_functions.php - Profile editing logic

## 📰 News Publication System
news.php - News display portal

newsGenerator.php - News content generator

template_news.php - News template engine

## 📚 Publications Management
publications.php - Scientific works management

publication_functions.php - Publication helper functions

## 🚀 Quick Start
<ol>
<li>Clone the repository:</li>

```bash
git clone https://github.com/yourusername/UniContent.git
```
<li>Configure authentication (in auth.php):</li>

```php
define('ADMIN_MODE', true);  // Enable admin privileges
```

<li>Local Development Setup</li>

1. **Start the local server in the application directory:**
   - Navigate to the application directory:
     ```bash
     cd UniContent/UniContent
     ```
   - Start PHP development server (replace XXXX with your port number, e.g., 8000):
     ```bash
     php -S localhost:XXXX
     ```

2. **Access the system in your browser:**
</ol>

## ⚙️ System Requirements
[<img src="images/PHP-logo.svg" width="80" alt="PHP Logo">](https://www.php.net/)

PHP 7.0 or higher

PHP LocalHost

Web server (Apache/Nginx recommended)

Write permissions for the output directories

## 🏗️ Architectural Highlights
<ul>
<li>Database-free file-based storage</li>
<li>Modular component architecture</li>
<li>Static HTML generation</li>
<li>Lightweight authentication system</li>
<li>Easy deployment with minimal dependencies</li>
</ul>

