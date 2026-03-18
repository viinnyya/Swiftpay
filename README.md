# SwiftPay Project

This is a simple web-based payment system built for the Advanced Database and Algorithms course. The application runs on XAMPP (Apache + PHP + MySQL) and uses a MySQL database to store users, accounts, transactions and more.

## Features

- Fund transfer between accounts
- Bills payment to utilities, banks, cable/Internet, government services, etc.
- Buy prepaid mobile load (Globe, Smart, DITO, TNT) with preset promo plans
- Savings tracker with allocations for travel, tuition and emergency
- Passcode check for every transaction

## Folder hierarchy

```
swiftpay/                root of project (copy to XAMPP htdocs)
├── css/                 stylesheets
│   └── style.css
├── js/                  client‑side scripts
│   └── script.js
├── php/                 PHP processing scripts and includes
│   ├── config.php       database connection
│   ├── auth.php         simple authentication helpers
│   ├── transfer.php     handle fund transfers
│   ├── bills.php        bills payment logic
│   ├── load.php         load purchase logic
│   ├── savings.php      savings management
│   └── passcode.php     passcode prompt/verify
├── db_init.sql          SQL initialization and sample data
└── index.php            landing page / dashboard
```

Place the entire `swiftpay` directory in `C:\xampp\htdocs` or your Apache document root, then open `http://localhost/swiftpay/` in a browser.

## Database setup

Use the provided `db_init.sql` script to create tables and stored procedures. Run it through phpMyAdmin or the MySQL command line.

```sql
-- Users table
CREATE TABLE Users (
    UserID INT PRIMARY KEY AUTO_INCREMENT,
    Username VARCHAR(50) UNIQUE NOT NULL,
    Passcode VARCHAR(10) NOT NULL, -- store hashed in production
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- account balances (linked to Users)
CREATE TABLE Accounts (
    AccountID INT PRIMARY KEY AUTO_INCREMENT,
    UserID INT NOT NULL,
    Balance DECIMAL(10,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (UserID) REFERENCES Users(UserID)
);

-- transaction log
CREATE TABLE TransactionLogs (
    LogID INT PRIMARY KEY AUTO_INCREMENT,
    SenderID INT,
    ReceiverID INT,
    Amount DECIMAL(10,2),
    Timestamp DATETIME,
    FOREIGN KEY (SenderID) REFERENCES Accounts(AccountID),
    FOREIGN KEY (ReceiverID) REFERENCES Accounts(AccountID)
);

-- utilities / billers example
CREATE TABLE Billers (
    BillerID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100) NOT NULL
);

-- load promo plans
CREATE TABLE LoadPlans (
    PlanID INT PRIMARY KEY AUTO_INCREMENT,
    Network ENUM('Globe','Smart','DITO','TNT') NOT NULL,
    Cost DECIMAL(10,2) NOT NULL,
    Description VARCHAR(255)
);

-- savings allocations
CREATE TABLE Savings (
    SavingID INT PRIMARY KEY AUTO_INCREMENT,
    AccountID INT NOT NULL,
    Travel DECIMAL(10,2) DEFAULT 0,
    Tuition DECIMAL(10,2) DEFAULT 0,
    Emergency DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (AccountID) REFERENCES Accounts(AccountID)
);

-- stored procedure from your existing code
DELIMITER //

CREATE PROCEDURE ProcessTransferWithSafety(
    IN p_sender_id INT,
    IN p_receiver_id INT,
    IN p_amount DECIMAL(10,2),
    OUT p_status_msg VARCHAR(100)
)
BEGIN
    DECLARE current_balance DECIMAL(10,2);

    SELECT Balance INTO current_balance FROM Accounts WHERE AccountID = p_sender_id;

    IF current_balance < p_amount THEN
        SET p_status_msg = 'Transaction Failed: Insufficient Funds';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient Funds';
    ELSE
        UPDATE Accounts SET Balance = Balance - p_amount WHERE AccountID = p_sender_id;
        UPDATE Accounts SET Balance = Balance + p_amount WHERE AccountID = p_receiver_id;
        INSERT INTO TransactionLogs (SenderID, ReceiverID, Amount, Timestamp)
        VALUES (p_sender_id, p_receiver_id, p_amount, NOW());
        SET p_status_msg = 'Transaction Successful';
    END IF;
END //

DELIMITER ;
``` 

Add sample data to `Billers` and `LoadPlans` after creating tables, e.g.: 

```sql
INSERT INTO Billers (Name) VALUES
('Electricity Utility'),
('Water District'),
('Internet Provider'),
('Bank Loan');

INSERT INTO LoadPlans (Network, Cost, Description) VALUES
('Globe',50,'50 pesos regular load'),
('Globe',100,'100 pesos promoflex'),
('Smart',200,'200 pesos value pack'),
('DITO',500,'500 pesos super load');
```

## UI overview

- `index.php` displays a dashboard with navigation links to each service.
- Each feature page (`transfer.php`, `bills.php`, `load.php`, `savings.php`) contains a form.
- When the user submits a transaction form, JavaScript shows a modal passcode prompt (in `js/script.js`) before the PHP script processes the request.
- CSS in `css/style.css` provides basic styling; you can expand it as needed.

## Passcode prompt

All transaction forms include a hidden field for passcode. Before submitting the form, `script.js` displays a popup asking for the passcode; the value is then inserted and the form submitted. The server-side scripts verify the passcode against the current user’s record (see `php/passcode.php`).

## Ideas for features expansion

- **Bills payment** – add categories: utilities, banks, insurance, government (e.g. SSS, Pag‑IBIG), rent, tuition fees, cable/Internet, mobile subscriptions.
- **Buy load** – offer promo plans at 50, 100, 200, 500 pesos with bonus data or calls; you can display recommended plans based on network.
- **Savings** – allow transfers between the three allocations, show pie chart of distribution, calculate total automatically.
- Add an **activity log** page showing recent `TransactionLogs` entries filtered by user.
- Consider using Bootstrap or another CSS framework for quicker UI development.

---

Feel free to adapt the code skeleton in this workspace to meet your professor’s requirements. The sample files below will get you started.