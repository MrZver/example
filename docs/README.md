Sales module
================

Sales module builds OrderManagement, Product Inventory, CheckoutManagement, integration for Payment & Shipping systems

- SalesService - facade for operations on goods, suppliers of these goods, prices
- CheckoutService - a facade that deals with the life cycle from adding to the cart to placing an order
- OrderService - manages and provides access to operations on orders (packages, items)
- PaymentService - deals with payments and returns of orders
- InvoiceService - invoices for orders (formation, sending, reports, storage)
- FinanceService - financial system (financial monitoring, reports, accounting), integrates ZohoBooks as a PaaS subsystem to OrderManagement
