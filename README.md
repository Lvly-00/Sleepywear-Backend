# Sleepywears.ph — Business Management System

## Team Information

* **Regino, Ruth May** — Project Manager & Software Quality Assurance
* **Pintes, Lovely Heart** — Full-Stack Developer
* **Latina, Sofia Isabel** — UI/UX Designer
* **Silverio, Myriah Vielle A.** — Business Analyst

---

## Project Overview and Objectives

**Sleepywears.ph** is an online clothing store that previously relied on manual processes such as handwritten invoices and paper-based order tracking. These methods were time-consuming, error-prone, and made it difficult to monitor sales and inventory accurately.

This **Business Management System** was developed to:

* Reduce manual workload and minimize human errors in transaction recording
* Replace paper-based processes with an automated, web-based system
* Improve efficiency in managing orders, generating invoices, and tracking inventory
* Provide clear insights into **gross and net income** for better decision-making
* Store and organize customer information for easier repeat transactions
* Support the digital transformation of Sleepywears.ph
* Ensure the system is user-friendly and accessible, even for users with limited technical skills

This system improves **accuracy, efficiency, organization, and overall business operations**.

---

## Tech Stack Used

**Frontend:**

* React Native

**Backend:**

* Laravel (REST API)

**Database:**

* NeonDB (PostgreSQL)

**Image Uploading:**

* Cloudinary

**Email Service:**

* Brevo API

---

## Setup and Installation Instructions

### Backend (Laravel API)

1. Clone the repository: https://github.com/Lvly-00/Sleepywear-Backend

   ```
   git clone <backend-repo-url>
   ```

2. Install dependencies:

   ```
   composer install
   ```

3. Create `.env` file and configure:
   
   ```
   cp .env.example .env
   ```
   * NeonDB credentials
   * Cloudinary keys
   * Brevo API key

4. Generate application key:

   ```
   php artisan key:generate
   ```

5. Run migrations:

   ```
   php artisan migrate --seed
   ```

6. Start the backend server:

   ```
   php artisan serve
   ```

---

### Mobile App (React Native)

1. Clone the repository: https://github.com/Lvly-00/Sleepywears-Mobile.git

   ```
   git clone <mobile-repo-url>
   ```

2. Install dependencies:

   ```
   npm install
   ```

3. Configure `.env` with backend API URL

4. Start the app:

   ```
   npx react-native run-android 
   ```
   or 
   ```
   npm start
   ```

---

## Deployment Links

**Mobile App:**
https://drive.google.com/drive/folders/1xLLpN9cdWZ6v0L2dS31Hdnbrprp9OeXz?usp=sharing

**Backend:**
https://sleepywear-backend.onrender.com

---

## Summary

The **Sleepywears.ph Business Management System** is an automated, web-based solution that modernizes business operations, reduces paperwork, improves accuracy, and enables smarter decision-making through real-time data tracking and reporting.

---

## Contact

For questions, support, or collaboration regarding the **Sleepywears.ph Business Management System**, please contact:

**Full-Stack Developer** Lovely Heart Pintes
* Email: lovelypintes@gmail.com
* GitHub: [https://github.com/Lvly-00](https://github.com/Lvly-00)

---

