-- create users table
CREATE TABLE IF NOT EXISTS Users (
    UserID INT PRIMARY KEY AUTO_INCREMENT,
    Username VARCHAR(50) UNIQUE NOT NULL,
    Password VARCHAR(255) NOT NULL,
    Passcode VARCHAR(10) NOT NULL,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- account balances (linked to Users; companies may have NULL UserID)
CREATE TABLE IF NOT EXISTS Accounts (
    AccountID INT PRIMARY KEY AUTO_INCREMENT,
    UserID INT NULL,
    Balance DECIMAL(10,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (UserID) REFERENCES Users(UserID)
);

-- transaction log
CREATE TABLE IF NOT EXISTS TransactionLogs (
    LogID INT PRIMARY KEY AUTO_INCREMENT,
    SenderID INT,
    ReceiverID INT,
    Amount DECIMAL(10,2),
    Timestamp DATETIME,
    FOREIGN KEY (SenderID) REFERENCES Accounts(AccountID),
    FOREIGN KEY (ReceiverID) REFERENCES Accounts(AccountID)
);

-- utilities / billers table (each biller has its own account)
CREATE TABLE IF NOT EXISTS Billers (
    BillerID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100) NOT NULL,
    AccountID INT,
    FOREIGN KEY (AccountID) REFERENCES Accounts(AccountID)
);

-- load promo plans (each provider also gets an account)
CREATE TABLE IF NOT EXISTS LoadPlans (
    PlanID INT PRIMARY KEY AUTO_INCREMENT,
    Network ENUM('Globe','Smart','DITO','TNT') NOT NULL,
    Cost DECIMAL(10,2) NOT NULL,
    Description VARCHAR(255),
    AccountID INT,
    FOREIGN KEY (AccountID) REFERENCES Accounts(AccountID)
);

-- savings allocations
CREATE TABLE IF NOT EXISTS Savings (
    SavingID INT PRIMARY KEY AUTO_INCREMENT,
    AccountID INT NOT NULL,
    Travel DECIMAL(10,2) DEFAULT 0,
    Tuition DECIMAL(10,2) DEFAULT 0,
    Emergency DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (AccountID) REFERENCES Accounts(AccountID)
);

-- stored procedure for safe transfer
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

-- sample data
-- create company accounts and link them
INSERT IGNORE INTO Accounts (UserID, Balance) VALUES
(NULL,0); SET @acct_elec = LAST_INSERT_ID();
INSERT IGNORE INTO Billers (Name, AccountID) VALUES
('Electricity Utility', @acct_elec);

INSERT IGNORE INTO Accounts (UserID, Balance) VALUES
(NULL,0); SET @acct_water = LAST_INSERT_ID();
INSERT IGNORE INTO Billers (Name, AccountID) VALUES
('Water District', @acct_water);

INSERT IGNORE INTO Accounts (UserID, Balance) VALUES
(NULL,0); SET @acct_net = LAST_INSERT_ID();
INSERT IGNORE INTO Billers (Name, AccountID) VALUES
('Internet Provider', @acct_net);

INSERT IGNORE INTO Accounts (UserID, Balance) VALUES
(NULL,0); SET @acct_bank = LAST_INSERT_ID();
INSERT IGNORE INTO Billers (Name, AccountID) VALUES
('Bank Loan', @acct_bank);

-- load providers
INSERT IGNORE INTO Accounts (UserID, Balance) VALUES
(NULL,0); SET @acct_globe = LAST_INSERT_ID();
INSERT IGNORE INTO LoadPlans (Network, Cost, Description, AccountID) VALUES
('Globe',50,'₱50 regular load', @acct_globe);

INSERT IGNORE INTO Accounts (UserID, Balance) VALUES
(NULL,0); SET @acct_globe2 = LAST_INSERT_ID();
INSERT IGNORE INTO LoadPlans (Network, Cost, Description, AccountID) VALUES
('Globe',100,'₱100 promoflex', @acct_globe2);

INSERT IGNORE INTO Accounts (UserID, Balance) VALUES
(NULL,0); SET @acct_smart = LAST_INSERT_ID();
INSERT IGNORE INTO LoadPlans (Network, Cost, Description, AccountID) VALUES
('Smart',200,'₱200 value pack', @acct_smart);

INSERT IGNORE INTO Accounts (UserID, Balance) VALUES
(NULL,0); SET @acct_dito = LAST_INSERT_ID();
INSERT IGNORE INTO LoadPlans (Network, Cost, Description, AccountID) VALUES
('DITO',500,'₱500 super load', @acct_dito);

-- optional sample users and accounts
INSERT IGNORE INTO Users (Username, Password, Passcode) VALUES
('alice','alicepwd','1234'),
('bob','bobpwd','0000');

INSERT IGNORE INTO Accounts (UserID, Balance) VALUES
((SELECT UserID FROM Users WHERE Username='alice'),1000.00),
((SELECT UserID FROM Users WHERE Username='bob'),500.00);