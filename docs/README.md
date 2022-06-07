Sales module
================

Sales модуль занимаеться построением OrderManagement, Product Inventory, CheckoutManagement, integration for Payment & Shipping systems

- SalesService - фасад для операций над товарами, поставщиками этих товаров, цены
- CheckoutService - фасад занимающийся жизненым циклом от добавления в корзину, до оформления заказа
- OrderService - управляет и предоставляет доступ до операций над заказами (пакетов, айтемов)
- PaymentService - занимается оплатами и возратами заказов
- InvoiceService - инвойсы к заказам (формирование, отсылка, отчеты, хранение)
- FinanceService - финансовая система (финмониторинг, репорты, бугхучет), интегрирует ZohoBooks, как PaaS подсистему к OrderManagement