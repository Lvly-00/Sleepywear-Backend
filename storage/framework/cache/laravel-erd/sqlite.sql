CREATE TABLE collection_sales_summary (
    id integer(11) NOT NULL AUTO_INCREMENT,
    user_id integer(11) NOT NULL,
    collection_id integer(11),
    collection_name varchar(255) NOT NULL,
    collection_capital numeric NOT NULL,
    date date NOT NULL,
    total_sales numeric NOT NULL,
    total_items_sold integer(11) NOT NULL,
    total_customers integer(11) NOT NULL,
    created_at datetime,
    updated_at datetime,
    PRIMARY KEY(id)
)
CREATE TABLE collections (
    id integer(11) NOT NULL AUTO_INCREMENT,
    user_id integer(11) NOT NULL,
    name varchar(255) NOT NULL,
    release_date date,
    qty integer(11) NOT NULL,
    capital integer(11) NOT NULL,
    total_sales integer(11) NOT NULL,
    stock_qty integer(11) NOT NULL,
    status varchar(255) NOT NULL DEFAULT Active,
    created_at datetime,
    updated_at datetime,
    PRIMARY KEY(id)
)
CREATE TABLE customers (
    id integer(11) NOT NULL AUTO_INCREMENT,
    user_id integer(11) NOT NULL,
    first_name varchar(255) NOT NULL,
    last_name varchar(255) NOT NULL,
    address text NOT NULL,
    contact_number varchar(255) NOT NULL,
    social_handle varchar(255) NOT NULL,
    created_at datetime,
    updated_at datetime,
    PRIMARY KEY(id)
)
CREATE TABLE invoices (
    id integer(11) NOT NULL AUTO_INCREMENT,
    user_id integer(11) NOT NULL,
    order_id integer(11) NOT NULL,
    total integer(11) NOT NULL,
    status varchar(255) NOT NULL DEFAULT Draft,
    created_at datetime,
    updated_at datetime,
    PRIMARY KEY(id)
)
CREATE TABLE items (
    id integer(11) NOT NULL AUTO_INCREMENT,
    user_id integer(11) NOT NULL,
    collection_id integer(11) NOT NULL,
    code varchar(255) NOT NULL,
    name varchar(255) NOT NULL,
    image varchar(255),
    price integer(11) NOT NULL,
    capital integer(11) NOT NULL,
    status varchar(255) NOT NULL DEFAULT Available,
    created_at datetime,
    updated_at datetime,
    PRIMARY KEY(id)
)
CREATE TABLE notifications (

)
CREATE TABLE order_items (
    id integer(11) NOT NULL AUTO_INCREMENT,
    user_id integer(11) NOT NULL,
    order_id integer(11) NOT NULL,
    item_id integer(11) NOT NULL,
    item_name varchar(255) NOT NULL,
    price integer(11) NOT NULL,
    quantity integer(11) NOT NULL DEFAULT 1,
    status varchar(255) NOT NULL DEFAULT Pending,
    created_at datetime,
    updated_at datetime,
    PRIMARY KEY(id)
)
CREATE TABLE orders (
    id integer(11) NOT NULL AUTO_INCREMENT,
    order_number integer(11) NOT NULL,
    user_id integer(11) NOT NULL,
    customer_id integer(11) NOT NULL,
    first_name varchar(255) NOT NULL,
    last_name varchar(255) NOT NULL,
    address text NOT NULL,
    contact_number varchar(255) NOT NULL,
    social_handle varchar(255) NOT NULL,
    order_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total integer(11) NOT NULL,
    created_at datetime,
    updated_at datetime,
    PRIMARY KEY(id)
)
CREATE TABLE payments (
    id integer(11) NOT NULL AUTO_INCREMENT,
    order_id integer(11) NOT NULL,
    payment_status varchar(255) NOT NULL DEFAULT Unpaid,
    payment_method varchar(255),
    total integer(11) NOT NULL,
    payment_date datetime,
    created_at datetime,
    updated_at datetime,
    PRIMARY KEY(id)
)
CREATE TABLE personal_access_tokens (
    id integer(11) NOT NULL AUTO_INCREMENT,
    tokenable_type varchar(255) NOT NULL,
    tokenable_id integer(11) NOT NULL,
    name text NOT NULL,
    token varchar(255) NOT NULL,
    abilities text,
    last_used_at datetime,
    expires_at datetime,
    created_at datetime,
    updated_at datetime,
    PRIMARY KEY(id)
)
CREATE TABLE users (
    id integer(11) NOT NULL AUTO_INCREMENT,
    name varchar(255),
    email varchar(255) NOT NULL,
    email_verified_at datetime,
    password varchar(255) NOT NULL,
    remember_token varchar(255),
    created_at datetime,
    updated_at datetime,
    PRIMARY KEY(id)
)
ALTER TABLE collection_sales_summary ADD FOREIGN KEY (collection_id) REFERENCES collections (id)
ALTER TABLE collections ADD FOREIGN KEY (id) REFERENCES items (collection_id)
ALTER TABLE customers ADD FOREIGN KEY (id) REFERENCES orders (customer_id)
ALTER TABLE invoices ADD FOREIGN KEY (order_id) REFERENCES orders (id)
ALTER TABLE items ADD FOREIGN KEY (id) REFERENCES order_items (item_id)
ALTER TABLE notifications ADD FOREIGN KEY (notifiable_id) REFERENCES users (id)
ALTER TABLE order_items ADD FOREIGN KEY (order_id) REFERENCES orders (id)
ALTER TABLE orders ADD FOREIGN KEY (id) REFERENCES payments (order_id)
ALTER TABLE personal_access_tokens ADD FOREIGN KEY (tokenable_id) REFERENCES users (id)