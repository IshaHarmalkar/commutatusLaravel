#Expense Sharing

##Tech Stack Used

---

- PHP >= 8.2
- Composer
- MySQL / MariaDB
- Node.js & NPM (optional – if frontend assets exist)
- Laravel >= 10
- Git

---

## Assumptions Made

- All users are friends to each other.

## Terms

-- Creditor is the person 1. who paid for the expense and in payments who the payment is being made to i.e sent_to.
-- Debtor are all participants who were involved in the expense - the creditor. In payment model it is the debtor, the person who makes the payment

## Api Endpoints

For authentication, laravel's breeze api was used. Which uses sanctum -> csrf tokens.
This includes csrf, register, login, logout.

### Users

| Method | Endpoint                 | Description                                                                                                         |
| ------ | ------------------------ | ------------------------------------------------------------------------------------------------------------------- |
| GET    | `/users/search`          | The search q is optional. Used to populated dropdowns. At max 20 users returned, else users can use search feature. |
| GET    | `/users/{user}/expenses` | Returns a list of expenses that user is involved in                                                                 |

###Epenses

| Method | Endpoint              | Description          |
| ------ | --------------------- | -------------------- |
| POST   | `/expense`            | Create a new expense |
| GET    | `/expenses/{expense}` | View expense details |

### Balance

| Method | Endpoint   | Description                       |
| ------ | ---------- | --------------------------------- |
| GET    | `/balance` | View current user balance summary |

### Payments

| Method | Endpoint    | Description                 |
| ------ | ----------- | --------------------------- |
| POST   | `/payments` | Record a payment settlement |
| GET    | `/payments` | List all payments           |

### Optimizations Required

-- 1. float division errors
-- 2. Debts are not simiplified using the graph method, only 1 to 1 are simplified
-- 3. Indexing yet to be done.
-- 4. Payments have to trigger a event, send it to creditor, to refresh it's dashboard. Did not priotize, as hosting platform dismisses ws.
