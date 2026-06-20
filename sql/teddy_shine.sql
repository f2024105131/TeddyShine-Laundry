-- ============================================
-- TEDDY SHINE LAUNDRY - MySQL DATABASE
-- ============================================

-- Drop database if exists
DROP DATABASE IF EXISTS TeddyShine;
CREATE DATABASE TeddyShine;
USE TeddyShine;

-- ============================================
-- SECTION 1: TABLES
-- ============================================

-- 1. RESIDENT
CREATE TABLE Resident (
    Resident_ID INT NOT NULL AUTO_INCREMENT,
    F_Name VARCHAR(80) NOT NULL,
    L_Name VARCHAR(80) NOT NULL,
    Phone_No VARCHAR(20),
    Email VARCHAR(150),
    City VARCHAR(100),
    Street VARCHAR(150),
    Area VARCHAR(100),
    House_No VARCHAR(50),
    Created_At DATETIME NOT NULL DEFAULT NOW(),
    CONSTRAINT pk_resident PRIMARY KEY (Resident_ID)
);

-- 2. SIGNUP
CREATE TABLE SignUp (
    SignUp_ID INT NOT NULL AUTO_INCREMENT,
    Resident_ID INT NOT NULL,
    Email VARCHAR(150) NOT NULL,
    Password VARCHAR(255) NOT NULL,
    Role VARCHAR(20) NOT NULL DEFAULT 'resident',
    Created_At DATETIME NOT NULL DEFAULT NOW(),
    CONSTRAINT pk_signup PRIMARY KEY (SignUp_ID),
    CONSTRAINT uq_signup_resident UNIQUE (Resident_ID),
    CONSTRAINT uq_signup_email UNIQUE (Email),
    CONSTRAINT fk_signup_resident FOREIGN KEY (Resident_ID)
        REFERENCES Resident(Resident_ID) ON DELETE CASCADE
);

-- 3. LOGIN
CREATE TABLE Login (
    Login_ID INT NOT NULL AUTO_INCREMENT,
    Resident_ID INT NOT NULL,
    Email VARCHAR(150) NOT NULL,
    Password VARCHAR(255) NOT NULL,
    Role VARCHAR(20) NOT NULL DEFAULT 'resident',
    Is_Active TINYINT NOT NULL DEFAULT 1,
    Last_Login DATETIME,
    Created_At DATETIME NOT NULL DEFAULT NOW(),
    CONSTRAINT pk_login PRIMARY KEY (Login_ID),
    CONSTRAINT uq_login_resident UNIQUE (Resident_ID),
    CONSTRAINT fk_login_resident FOREIGN KEY (Resident_ID)
        REFERENCES Resident(Resident_ID) ON DELETE CASCADE
);

-- 4. STAFF
CREATE TABLE Staff (
    Staff_ID INT NOT NULL AUTO_INCREMENT,
    Staff_Name VARCHAR(100) NOT NULL,
    Contact_No VARCHAR(20),
    Email VARCHAR(150),
    Shift_Start TIME,
    Shift_End TIME,
    Role VARCHAR(20) NOT NULL,
    Order_ID INT,
    CONSTRAINT pk_staff PRIMARY KEY (Staff_ID)
);

-- 5. DELIVERY SLOTS
CREATE TABLE DeliverySlots (
    Slot_ID INT NOT NULL AUTO_INCREMENT,
    Slot_Type VARCHAR(50),
    Start_Time TIME NOT NULL,
    End_Time TIME NOT NULL,
    Max_Orders INT NOT NULL DEFAULT 10,
    CONSTRAINT pk_deliveryslots PRIMARY KEY (Slot_ID),
    CONSTRAINT chk_slot_times CHECK (End_Time > Start_Time)
);

-- 6. LAUNDRY ITEM
CREATE TABLE LaundryItem (
    Item_ID INT NOT NULL AUTO_INCREMENT,
    Color VARCHAR(50),
    Quantity INT NOT NULL DEFAULT 1,
    Cloth_Type VARCHAR(100),
    CONSTRAINT pk_laundryitem PRIMARY KEY (Item_ID),
    CONSTRAINT chk_item_qty CHECK (Quantity > 0)
);

-- 7. PROCESS STAGE
CREATE TABLE ProcessStage (
    Stage_ID INT NOT NULL AUTO_INCREMENT,
    Stages_name VARCHAR(20) NOT NULL,
    CONSTRAINT pk_processstage PRIMARY KEY (Stage_ID)
);

-- 8. SERVICES
CREATE TABLE Services (
    Service_ID INT NOT NULL AUTO_INCREMENT,
    Service_Name VARCHAR(100) NOT NULL,
    Service_Price DECIMAL(10,2) NOT NULL,
    Estimate_Time INT,
    Status VARCHAR(50) NOT NULL DEFAULT 'Active',
    CONSTRAINT pk_services PRIMARY KEY (Service_ID),
    CONSTRAINT chk_svc_price CHECK (Service_Price >= 0)
);

-- 9. ORDERS
CREATE TABLE Orders (
    Order_ID INT NOT NULL AUTO_INCREMENT,
    Resident_ID INT NOT NULL,
    Staff_ID INT,
    Slot_ID INT,
    Order_Date DATE NOT NULL,
    Delivery_Date DATE,
    Amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    Status VARCHAR(50) NOT NULL DEFAULT 'Pending',
    CONSTRAINT pk_orders PRIMARY KEY (Order_ID),
    CONSTRAINT fk_order_resident FOREIGN KEY (Resident_ID)
        REFERENCES Resident(Resident_ID),
    CONSTRAINT fk_order_staff FOREIGN KEY (Staff_ID)
        REFERENCES Staff(Staff_ID),
    CONSTRAINT fk_order_slot FOREIGN KEY (Slot_ID)
        REFERENCES DeliverySlots(Slot_ID),
    CONSTRAINT chk_order_amount CHECK (Amount >= 0)
);

-- 10. ADD circular FK from Staff to Orders
ALTER TABLE Staff
    ADD CONSTRAINT fk_staff_order 
    FOREIGN KEY (Order_ID) 
    REFERENCES Orders(Order_ID) ON DELETE SET NULL;

-- 11. ORDER ITEMS
CREATE TABLE OrderItems (
    OrderItem_ID INT NOT NULL AUTO_INCREMENT,
    Order_ID INT NOT NULL,
    Service_ID INT NOT NULL,
    Quantity INT NOT NULL DEFAULT 1,
    Price DECIMAL(10,2) NOT NULL,
    CONSTRAINT pk_orderitems PRIMARY KEY (OrderItem_ID),
    CONSTRAINT fk_oi_order FOREIGN KEY (Order_ID)
        REFERENCES Orders(Order_ID) ON DELETE CASCADE,
    CONSTRAINT fk_oi_service FOREIGN KEY (Service_ID)
        REFERENCES Services(Service_ID),
    CONSTRAINT chk_oi_qty CHECK (Quantity > 0),
    CONSTRAINT chk_oi_price CHECK (Price >= 0)
);

-- 12. TRACKING
CREATE TABLE Tracking (
    Tracking_ID INT NOT NULL AUTO_INCREMENT,
    Item_ID INT NOT NULL,
    Stage_ID INT NOT NULL,
    Staff_ID INT,
    Start_Time DATETIME,
    End_Time DATETIME,
    Status VARCHAR(50) NOT NULL DEFAULT 'In Progress',
    CONSTRAINT pk_tracking PRIMARY KEY (Tracking_ID),
    CONSTRAINT fk_track_item FOREIGN KEY (Item_ID)
        REFERENCES LaundryItem(Item_ID),
    CONSTRAINT fk_track_stage FOREIGN KEY (Stage_ID)
        REFERENCES ProcessStage(Stage_ID),
    CONSTRAINT fk_track_staff FOREIGN KEY (Staff_ID)
        REFERENCES Staff(Staff_ID),
    CONSTRAINT chk_track_times CHECK (End_Time IS NULL OR End_Time >= Start_Time)
);

-- 13. INVOICE
CREATE TABLE Invoice (
    Invoice_ID INT NOT NULL AUTO_INCREMENT,
    Order_ID INT NOT NULL,
    Resident_ID INT NOT NULL,
    Total_Amount DECIMAL(10,2) NOT NULL,
    Discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    Final_Amount DECIMAL(10,2) NOT NULL,
    Invoice_Date DATE NOT NULL,
    Invoice_Status VARCHAR(20) NOT NULL DEFAULT 'Unpaid',
    CONSTRAINT pk_invoice PRIMARY KEY (Invoice_ID),
    CONSTRAINT uq_invoice_order UNIQUE (Order_ID),
    CONSTRAINT fk_inv_order FOREIGN KEY (Order_ID)
        REFERENCES Orders(Order_ID),
    CONSTRAINT fk_inv_resident FOREIGN KEY (Resident_ID)
        REFERENCES Resident(Resident_ID),
    CONSTRAINT chk_inv_discount CHECK (Discount >= 0 AND Discount <= Total_Amount),
    CONSTRAINT chk_inv_final CHECK (Final_Amount >= 0)
);

-- 14. PAYMENTS
CREATE TABLE Payments (
    Payment_ID INT NOT NULL AUTO_INCREMENT,
    Invoice_ID INT NOT NULL,
    Payment_Date DATE NOT NULL,
    Payment_Amount DECIMAL(10,2) NOT NULL,
    Payment_methods VARCHAR(20) NOT NULL,
    Payment_Status VARCHAR(20) NOT NULL DEFAULT 'Completed',
    CONSTRAINT pk_payments PRIMARY KEY (Payment_ID),
    CONSTRAINT fk_pay_invoice FOREIGN KEY (Invoice_ID)
        REFERENCES Invoice(Invoice_ID),
    CONSTRAINT chk_pay_amount CHECK (Payment_Amount > 0)
);

-- 15. RECORDS
CREATE TABLE Records (
    Payment_ID INT NOT NULL,
    Record_ID INT NOT NULL,
    Recorded_At DATETIME NOT NULL DEFAULT NOW(),
    Notes VARCHAR(255),
    CONSTRAINT pk_records PRIMARY KEY (Payment_ID, Record_ID),
    CONSTRAINT fk_records_payment FOREIGN KEY (Payment_ID)
        REFERENCES Payments(Payment_ID) ON DELETE CASCADE
);

-- 16. PRINT
CREATE TABLE `Print` (
    Print_ID INT NOT NULL AUTO_INCREMENT,
    Invoice_ID INT NOT NULL,
    Printed_At DATETIME NOT NULL DEFAULT NOW(),
    CONSTRAINT pk_print PRIMARY KEY (Print_ID),
    CONSTRAINT uq_print_invoice UNIQUE (Invoice_ID),
    CONSTRAINT fk_print_invoice FOREIGN KEY (Invoice_ID)
        REFERENCES Invoice(Invoice_ID)
);

-- ============================================
-- SECTION 2: INDEXES
-- ============================================

CREATE INDEX idx_resident_email ON Resident (Email);
CREATE INDEX idx_signup_email ON SignUp (Email);
CREATE INDEX idx_login_email ON Login (Email);
CREATE INDEX idx_orders_resident ON Orders (Resident_ID);
CREATE INDEX idx_orders_staff ON Orders (Staff_ID);
CREATE INDEX idx_orders_status ON Orders (Status);
CREATE INDEX idx_orders_date ON Orders (Order_Date);
CREATE INDEX idx_orderitems_order ON OrderItems (Order_ID);
CREATE INDEX idx_orderitems_service ON OrderItems (Service_ID);
CREATE INDEX idx_tracking_item ON Tracking (Item_ID);
CREATE INDEX idx_tracking_stage ON Tracking (Stage_ID);
CREATE INDEX idx_tracking_staff ON Tracking (Staff_ID);
CREATE INDEX idx_invoice_status ON Invoice (Invoice_Status);
CREATE INDEX idx_invoice_date ON Invoice (Invoice_Date);
CREATE INDEX idx_payments_invoice ON Payments (Invoice_ID);
CREATE INDEX idx_payments_date ON Payments (Payment_Date);
CREATE INDEX idx_payments_method ON Payments (Payment_methods);

-- ============================================
-- SECTION 3: TRIGGERS (MySQL syntax)
-- ============================================

DELIMITER //

-- T1: Auto-update Order Amount after INSERT
CREATE TRIGGER trg_orderitems_after_insert
AFTER INSERT ON OrderItems
FOR EACH ROW
BEGIN
    UPDATE Orders o
    SET Amount = (
        SELECT COALESCE(SUM(oi.Price * oi.Quantity), 0)
        FROM OrderItems oi
        WHERE oi.Order_ID = o.Order_ID
    )
    WHERE o.Order_ID = NEW.Order_ID;
END//

-- T1: Auto-update Order Amount after UPDATE
CREATE TRIGGER trg_orderitems_after_update
AFTER UPDATE ON OrderItems
FOR EACH ROW
BEGIN
    UPDATE Orders o
    SET Amount = (
        SELECT COALESCE(SUM(oi.Price * oi.Quantity), 0)
        FROM OrderItems oi
        WHERE oi.Order_ID = o.Order_ID
    )
    WHERE o.Order_ID = NEW.Order_ID;
END//

-- T1: Auto-update Order Amount after DELETE
CREATE TRIGGER trg_orderitems_after_delete
AFTER DELETE ON OrderItems
FOR EACH ROW
BEGIN
    UPDATE Orders o
    SET Amount = (
        SELECT COALESCE(SUM(oi.Price * oi.Quantity), 0)
        FROM OrderItems oi
        WHERE oi.Order_ID = o.Order_ID
    )
    WHERE o.Order_ID = OLD.Order_ID;
END//

-- T2: Auto-compute Invoice Final_Amount on INSERT
CREATE TRIGGER trg_invoice_before_insert
BEFORE INSERT ON Invoice
FOR EACH ROW
BEGIN
    SET NEW.Final_Amount = NEW.Total_Amount - NEW.Discount;
END//

-- T2: Auto-compute Invoice Final_Amount on UPDATE
CREATE TRIGGER trg_invoice_before_update
BEFORE UPDATE ON Invoice
FOR EACH ROW
BEGIN
    SET NEW.Final_Amount = NEW.Total_Amount - NEW.Discount;
END//

-- T3: Auto-update Invoice Status after Payment
CREATE TRIGGER trg_payments_after_insert
AFTER INSERT ON Payments
FOR EACH ROW
BEGIN
    DECLARE v_final DECIMAL(10,2);
    DECLARE v_paid DECIMAL(10,2);
    
    SELECT Final_Amount INTO v_final
    FROM Invoice WHERE Invoice_ID = NEW.Invoice_ID;
    
    SELECT COALESCE(SUM(Payment_Amount), 0) INTO v_paid
    FROM Payments WHERE Invoice_ID = NEW.Invoice_ID;
    
    UPDATE Invoice
    SET Invoice_Status = CASE
        WHEN v_paid >= v_final THEN 'Paid'
        WHEN v_paid > 0 THEN 'Partial'
        ELSE 'Unpaid'
    END
    WHERE Invoice_ID = NEW.Invoice_ID;
END//

-- T4: Prevent payment exceeding balance
CREATE TRIGGER trg_payments_before_insert
BEFORE INSERT ON Payments
FOR EACH ROW
BEGIN
    DECLARE v_final DECIMAL(10,2);
    DECLARE v_paid DECIMAL(10,2);
    DECLARE v_balance DECIMAL(10,2);
    
    SELECT Final_Amount INTO v_final
    FROM Invoice WHERE Invoice_ID = NEW.Invoice_ID;
    
    SELECT COALESCE(SUM(Payment_Amount), 0) INTO v_paid
    FROM Payments WHERE Invoice_ID = NEW.Invoice_ID;
    
    SET v_balance = v_final - v_paid;
    
    IF NEW.Payment_Amount > v_balance THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Payment amount exceeds outstanding invoice balance.';
    END IF;
END//

-- T5: Auto-set Tracking Status to Completed
CREATE TRIGGER trg_tracking_before_update
BEFORE UPDATE ON Tracking
FOR EACH ROW
BEGIN
    IF NEW.End_Time IS NOT NULL AND OLD.End_Time IS NULL THEN
        SET NEW.Status = 'Completed';
    END IF;
END//

DELIMITER ;

-- ============================================
-- SECTION 4: VIEWS
-- ============================================

CREATE VIEW v_Invoice_Payment_Summary AS
SELECT
    i.Invoice_ID,
    i.Order_ID,
    o.Resident_ID,
    CONCAT(r.F_Name, ' ', r.L_Name) AS Resident_Name,
    i.Total_Amount,
    i.Discount,
    i.Final_Amount AS Total_Due,
    COALESCE(SUM(p.Payment_Amount), 0) AS Total_Paid,
    i.Final_Amount - COALESCE(SUM(p.Payment_Amount), 0) AS Remaining,
    i.Invoice_Status,
    i.Invoice_Date
FROM Invoice i
JOIN Orders o ON o.Order_ID = i.Order_ID
JOIN Resident r ON r.Resident_ID = o.Resident_ID
LEFT JOIN Payments p ON p.Invoice_ID = i.Invoice_ID
GROUP BY
    i.Invoice_ID, i.Order_ID, o.Resident_ID,
    r.F_Name, r.L_Name,
    i.Total_Amount, i.Discount, i.Final_Amount,
    i.Invoice_Status, i.Invoice_Date;

CREATE VIEW v_Print_Receipt AS
SELECT
    pr.Print_ID,
    pr.Invoice_ID,
    pr.Printed_At,
    s.Resident_Name,
    s.Total_Due,
    s.Total_Paid AS Total_Payment,
    s.Remaining AS Total_Unpaid,
    s.Invoice_Status
FROM `Print` pr
JOIN v_Invoice_Payment_Summary s ON s.Invoice_ID = pr.Invoice_ID;

CREATE VIEW v_Monthly_Records AS
SELECT
    DATE_FORMAT(p.Payment_Date, '%Y-%m') AS Month,
    COUNT(DISTINCT i.Invoice_ID) AS Total_Invoices,
    SUM(CASE WHEN i.Invoice_Status = 'Paid' THEN 1 ELSE 0 END) AS Paid_Invoices,
    SUM(CASE WHEN i.Invoice_Status != 'Paid' THEN 1 ELSE 0 END) AS Unpaid_Invoices,
    SUM(p.Payment_Amount) AS Total_Collected,
    SUM(i.Final_Amount) - SUM(p.Payment_Amount) AS Remaining
FROM Payments p
JOIN Invoice i ON i.Invoice_ID = p.Invoice_ID
GROUP BY DATE_FORMAT(p.Payment_Date, '%Y-%m');

CREATE VIEW v_Order_Payments AS
SELECT
    o.Order_ID,
    o.Order_Date,
    o.Status AS Order_Status,
    CONCAT(r.F_Name, ' ', r.L_Name) AS Resident_Name,
    r.Phone_No,
    i.Invoice_ID,
    i.Final_Amount AS Invoice_Amount,
    i.Invoice_Status,
    p.Payment_ID,
    p.Payment_Date,
    p.Payment_Amount,
    p.Payment_methods AS Payment_Method,
    p.Payment_Status
FROM Orders o
JOIN Resident r ON r.Resident_ID = o.Resident_ID
JOIN Invoice i ON i.Order_ID = o.Order_ID
LEFT JOIN Payments p ON p.Invoice_ID = i.Invoice_ID;

CREATE VIEW v_Staff_Workload AS
SELECT
    s.Staff_ID,
    s.Staff_Name,
    s.Role,
    COUNT(DISTINCT o.Order_ID) AS Orders_Managed,
    COUNT(DISTINCT t.Tracking_ID) AS Items_Tracked
FROM Staff s
LEFT JOIN Orders o ON o.Staff_ID = s.Staff_ID
LEFT JOIN Tracking t ON t.Staff_ID = s.Staff_ID
GROUP BY s.Staff_ID, s.Staff_Name, s.Role;

-- ============================================
-- SECTION 5: SAMPLE DATA
-- ============================================

INSERT INTO ProcessStage (Stages_name) VALUES
('Washing'),
('Drying'),
('Ironing'),
('Packing'),
('Delivery');

INSERT INTO Services (Service_Name, Service_Price, Estimate_Time, Status) VALUES
('Wash & Fold', 150.00, 120, 'Active'),
('Dry Cleaning', 300.00, 240, 'Active'),
('Ironing Only', 80.00, 60, 'Active'),
('Wash & Iron', 200.00, 180, 'Active'),
('Stain Removal', 250.00, 150, 'Active'),
('Bulk Laundry', 500.00, 360, 'Active');

INSERT INTO DeliverySlots (Slot_Type, Start_Time, End_Time, Max_Orders) VALUES
('Morning', '08:00:00', '11:00:00', 10),
('Afternoon', '12:00:00', '15:00:00', 10),
('Evening', '17:00:00', '20:00:00', 8);

INSERT INTO Staff (Staff_Name, Contact_No, Email, Shift_Start, Shift_End, Role, Order_ID) VALUES
('Ahmed Khan', '03001234567', 'ahmed@laundryms.com', '08:00:00', '16:00:00', 'collector', NULL),
('Sara Malik', '03019876543', 'sara@laundryms.com', '07:00:00', '15:00:00', 'washer', NULL),
('Bilal Hussain', '03331122334', 'bilal@laundryms.com', '12:00:00', '20:00:00', 'washer', NULL),
('Nadia Iqbal', '03215566778', 'nadia@laundryms.com', '09:00:00', '17:00:00', 'deliveryboy', NULL),
('Usman Raza', '03004433221', 'usman@laundryms.com', '14:00:00', '22:00:00', 'deliveryboy', NULL);

INSERT INTO Resident (F_Name, L_Name, Phone_No, Email, City, Street, Area, House_No) VALUES
('Ali', 'Hassan', '03111234567', 'ali@email.com', 'Lahore', 'Main Blvd', 'Gulberg', 'H-12'),
('Fatima', 'Sheikh', '03229876543', 'fatima@email.com', 'Lahore', 'Garden Road', 'DHA Phase 5', 'B-45'),
('Omar', 'Farooq', '03334455667', 'omar@email.com', 'Lahore', 'Canal Road', 'Johar Town', 'A-3'),
('Ayesha', 'Siddiqui', '03445566778', 'ayesha@email.com', 'Lahore', 'Jail Road', 'Model Town', 'C-7'),
('Tariq', 'Mehmood', '03556677889', 'tariq@email.com', 'Lahore', 'Ferozepur Rd', 'Iqbal Town', 'D-22');

INSERT INTO SignUp (Resident_ID, Email, Password, Role) VALUES
(1, 'ali@email.com', '$2b$12$hashedpassword1', 'resident'),
(2, 'fatima@email.com', '$2b$12$hashedpassword2', 'resident'),
(3, 'omar@email.com', '$2b$12$hashedpassword3', 'resident'),
(4, 'ayesha@email.com', '$2b$12$hashedpassword4', 'resident'),
(5, 'tariq@email.com', '$2b$12$hashedpassword5', 'admin');

INSERT INTO Login (Resident_ID, Email, Password, Role, Is_Active, Last_Login) VALUES
(1, 'ali@email.com', '$2b$12$hashedpassword1', 'resident', 1, '2025-05-01 09:30:00'),
(2, 'fatima@email.com', '$2b$12$hashedpassword2', 'resident', 1, '2025-05-02 11:00:00'),
(3, 'omar@email.com', '$2b$12$hashedpassword3', 'resident', 1, '2025-05-03 14:15:00'),
(4, 'ayesha@email.com', '$2b$12$hashedpassword4', 'resident', 1, '2025-05-04 16:45:00'),
(5, 'tariq@email.com', '$2b$12$hashedpassword5', 'admin', 1, '2025-05-05 08:00:00');

INSERT INTO LaundryItem (Color, Quantity, Cloth_Type) VALUES
('White', 3, 'Cotton Shirt'),
('Blue', 2, 'Denim Jeans'),
('Black', 4, 'Wool Sweater'),
('White', 5, 'Cotton Bedsheet'),
('Grey', 2, 'Polyester Jacket'),
('Red', 1, 'Silk Dress'),
('Brown', 3, 'Linen Trousers');

INSERT INTO Orders (Resident_ID, Staff_ID, Slot_ID, Order_Date, Delivery_Date, Status) VALUES
(1, 1, 1, '2025-05-01', '2025-05-03', 'Completed'),
(2, 1, 2, '2025-05-02', '2025-05-04', 'Completed'),
(3, 1, 1, '2025-05-03', '2025-05-05', 'In Progress'),
(4, 1, 3, '2025-05-04', '2025-05-06', 'Pending'),
(5, 1, 2, '2025-05-05', '2025-05-07', 'Pending');

INSERT INTO OrderItems (Order_ID, Service_ID, Quantity, Price) VALUES
(1, 1, 2, 150.00),
(1, 3, 1, 80.00),
(2, 2, 1, 300.00),
(2, 5, 1, 250.00),
(3, 4, 2, 200.00),
(4, 1, 3, 150.00),
(5, 6, 1, 500.00);

INSERT INTO Tracking (Item_ID, Stage_ID, Staff_ID, Start_Time, End_Time, Status) VALUES
(1, 1, 2, '2025-05-01 09:00:00', '2025-05-01 11:00:00', 'Completed'),
(1, 2, 2, '2025-05-01 11:30:00', '2025-05-01 13:00:00', 'Completed'),
(1, 3, 3, '2025-05-01 13:30:00', '2025-05-01 14:30:00', 'Completed'),
(1, 4, 3, '2025-05-01 15:00:00', '2025-05-01 15:30:00', 'Completed'),
(1, 5, 4, '2025-05-03 09:00:00', '2025-05-03 10:00:00', 'Completed'),
(2, 1, 2, '2025-05-02 10:00:00', '2025-05-02 12:00:00', 'Completed'),
(2, 2, 3, '2025-05-02 12:30:00', '2025-05-02 14:00:00', 'Completed'),
(3, 1, 2, '2025-05-03 09:00:00', NULL, 'In Progress');

INSERT INTO Invoice (Order_ID, Resident_ID, Total_Amount, Discount, Invoice_Date, Invoice_Status) VALUES
(1, 1, 380.00, 30.00, '2025-05-01', 'Unpaid'),
(2, 2, 550.00, 0.00, '2025-05-02', 'Unpaid'),
(3, 3, 400.00, 20.00, '2025-05-03', 'Unpaid'),
(4, 4, 450.00, 0.00, '2025-05-04', 'Unpaid'),
(5, 5, 500.00, 50.00, '2025-05-05', 'Unpaid');

INSERT INTO Payments (Invoice_ID, Payment_Date, Payment_Amount, Payment_methods, Payment_Status) VALUES
(1, '2025-05-03', 350.00, 'cash', 'Completed'),
(2, '2025-05-04', 550.00, 'Online', 'Completed'),
(3, '2025-05-05', 200.00, 'Card', 'Completed'),
(4, '2025-05-06', 100.00, 'cash', 'Completed');

INSERT INTO Records (Payment_ID, Record_ID, Notes) VALUES
(1, 1, 'Full settlement for Order 1'),
(2, 1, 'Full settlement for Order 2'),
(3, 1, 'Partial payment for Order 3'),
(4, 1, 'Partial payment for Order 4');

INSERT INTO `Print` (Invoice_ID, Printed_At) VALUES
(1, '2025-05-03 10:30:00'),
(2, '2025-05-04 11:00:00');

-- ============================================
-- SECTION 6: VERIFICATION QUERIES
-- ============================================

SELECT * FROM v_Order_Payments;
SELECT * FROM v_Invoice_Payment_Summary;
SELECT * FROM v_Print_Receipt;
SELECT * FROM v_Monthly_Records;
SELECT * FROM v_Staff_Workload;