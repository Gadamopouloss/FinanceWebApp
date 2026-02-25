# Online Financial Management System

### A Full-Stack Web Application for Personal Financial Control

Developed as a Final Thesis Project – Department of Computer Science & Telecommunications

---

## 📌 Overview

The **Online Financial Management System** is a full-stack web application designed to help users efficiently manage, monitor, and analyze their personal finances.

The system enables structured budgeting, expense tracking, savings goal management, and financial visualization through dynamic statistical charts.

It was developed as an academic thesis project with a strong focus on:

* Personal financial awareness
* Digital transaction management
* Overconsumption reduction
* Secure user-based data handling
* Practical implementation of client-server architecture

---

##  Core Features

### 🔐 Authentication & User Management

* Secure user registration
* Login / Logout functionality
* Session-based authentication
* User-specific data isolation
* Controlled access to financial data

---

### 💳 Transaction Management

* Add income transactions
* Add expense transactions
* Categorized financial records
* Monthly & yearly filtering
* Complete transaction history
* Real-time balance calculation

---

### 📊 Financial Analytics & Visualization

* Income vs Expenses comparison
* Monthly summaries
* Spending trend analysis
* Dynamic chart rendering using JavaScript & Canvas API
* Statistical overview dashboard

---

### 🎯 Savings Goals

* Create personalized saving goals
* Track progress per goal
* Monitor saved vs target amount
* Goal-based financial planning

---

### 🔁 Peer-to-Peer Money Transfers

* Transfer money between registered users
* Automatic balance updates
* Transaction recording for both parties
* Email notification for successful transfers

---

### 📧 Email Notification System

* SMTP-based mailing functionality
* Transfer confirmation emails
* System notification handling via Composer dependencies

---

### 📚 Informational & Advisory Pages

* Financial tips section
* FAQ
* Terms & Conditions
* Privacy Policy
* Smart financial advice integration

---

## 🏗️ System Architecture

The application follows a **classic Client–Server Architecture**.

```
Client (Browser)
     ↓
Frontend (HTML / CSS / JS / Bootstrap)
     ↓ AJAX Requests
Backend (PHP – Core)
     ↓
MySQL Database
```

### 🔹 Frontend

* HTML5
* CSS3
* Bootstrap (Responsive UI)
* JavaScript (ES6)
* AJAX (asynchronous updates)
* Canvas API (dynamic chart rendering)

### 🔹 Backend

* Core PHP (no heavy frameworks)
* Server-side validation
* Session management
* Business logic handling
* SMTP integration for email services

### 🔹 Database

* MySQL (Relational Model)
* Structured entity relationships
* User-based data isolation
* Referential integrity between transactions & transfers

---

## 🗄️ Conceptual Database Design

### Main Entities

* `users`
* `transactions`
* `goals`
* `transfers`

### Relationships

* One user → Many transactions
* One user → Many goals
* Transfers → Reference sender & receiver (user-to-user relation)
* Each transaction is linked to a specific authenticated user

---

## 📂 Project Structure

```
.
.
├── README.md
├── composer.json
├── composer.lock
├── db_connection.php
├── index.php
├── login.php
├── register.php
├── logout.php
├── user.php
├── send_money.php
├── request.php
├── submit.php
├── edit_transaction.php
├── edit_description.php
├── tagged_transactions.php
├── history.php
├── monthly.php
├── yearly.php
├── total.php
├── report.php
├── goal.php
├── charts.php
├── contact.php
├── sendcontacts.php
├── index.js
├── styles.css
├── vendor/                # Composer dependencies
└── main1/                 # Frontend (static pages)
    ├── index.html
    ├── faq.html
    ├── privacy.html
    ├── terms.html
    ├── tips.html
    ├── faqs.js
    ├── tips.js
    ├── imgs/              # Static images
    └── style/             # CSS files


```

---

## 🛠️ Technology Stack

| Technology           | Purpose                        |
| -------------------- | ------------------------------ |
| **PHP**              | Server-side logic & validation |
| **MySQL**            | Relational database            |
| **JavaScript (ES6)** | Dynamic interaction            |
| **AJAX**             | Asynchronous communication     |
| **Bootstrap**        | Responsive UI framework        |
| **Canvas API**       | Financial data visualization   |
| **Composer**         | Dependency management          |
| **SMTP**             | Email notification system      |

---

## ⚙️ Installation Guide

### 🔹 Requirements

* PHP 8+
* MySQL / MariaDB
* Apache (XAMPP / WAMP recommended)
* Composer

---

### 🔹 Setup Instructions

-> Clone the repository:

```bash
git clone https://github.com/your-username/financial-management-system.git
```

-> Move the project folder into:

* `htdocs/` (XAMPP)
  or
* your server root directory

-> Create a database:

```sql
CREATE DATABASE financial_management;
```

-> Import the provided `.sql` file via phpMyAdmin.

-> Configure database credentials in:

```
db_connection.php
```

-> Install dependencies:

```bash
composer install
```

-> Start Apache & MySQL.

-> Open in browser:

```
http://localhost/financial-management-system
```

---

## 🔒 Security Considerations

* Session-based authentication
* Controlled access per user
* Server-side validation
* Email verification logic
* Structured transfer validation
* Prepared statements (recommended for SQL injection prevention)
* Isolated user financial records

> ⚠️ Note: Additional hardening (CSRF tokens, password hashing best practices, rate limiting) is recommended for production deployment.

---

## 🎓 Academic Context

This project was developed as a **Final Thesis** titled:

> “Online Financial Management Application”

The research explores:

* Digital economy risks
* Overconsumption patterns
* Personal financial discipline
* Technological influence on economic behavior
* Design & implementation of a secure web-based financial system

---


## 👨‍💻 Author

**Grigoris Adamopoulos**
Department of Informatics and Telecommunications
University of Thessaly 
---

## 📜 License

Developed for academic purposes.
Not intended for commercial production use without additional security and scalability improvements.

---

---

