# Sleepywears.ph — Business Management System

## Team Information

* **Regino, Ruth May** — Project Manager
* **Pintes, Lovely Heart** — Full-Stack Developer
* **Latina, Sofia Isabel** — UI/UX Designer
* **Del Pilar, Kirk Percival** — Software Quality Assurance
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

## 🛠 Tech Stack Used

**Frontend:**

* React.js

**Backend:**

* Laravel (REST API)

**Database:**

* NeonDB (PostgreSQL)

**Image Uploading:**

* Cloudinary

**Email Service:**

* Brevo API

---

## ⚙️ Setup and Installation Instructions

### Backend (Laravel API)

1. Clone the repository:

   ```
   git clone <backend-repo-url>
   ```

2. Navigate to the backend folder:

   ```
   cd backend   # adjust if your backend folder is named differently
   ```

3. Install dependencies:

   ```
   composer install
   ```

4. Create `.env` file and configure:
   
   ```
   cp .env.example .env
   ```
   * NeonDB credentials
   * Cloudinary keys
   * Brevo API key

6. Generate application key:

   ```
   php artisan key:generate
   ```

7. Run migrations:

   ```
   php artisan migrate --seed
   ```

8. Start the backend server:

   ```
   php artisan serve
   ```

---

### Frontend (React.js)

1. Clone the repository: https://github.com/Lvly-00/Sleepywear-Frontend.git

   ```
   git clone <frontend-repo-url>
   ```
   

3. Install dependencies:

   ```
   npm install
   ```

4. Configure `.env` with backend API URL

5. Start the app:

   ```
   npm start
   ```

---

## Deployment Links

**Frontend:**
https://sleepywear-frontend.onrender.com

**Backend:**
https://sleepywear-backend.onrender.com

---

## Summary

The **Sleepywears.ph Business Management System** is an automated, web-based solution that modernizes business operations, reduces paperwork, improves accuracy, and enables smarter decision-making through real-time data tracking and reporting.

---

## Contact

For questions, support, or collaboration regarding the **Sleepywears.ph Business Management System**, please contact:

**Project Lead / Full-Stack Developer** Lovely Heart Pintes
* Email: lovelypintes@gmail.com
* GitHub: [https://github.com/Lvly-00](https://github.com/Lvly-00)

---

