USE brightloop_subscription_db;
INSERT INTO taxes (name,percentage,tax_type,applies_to_all) VALUES ('GST 18%',18.00,'GST',1);
INSERT INTO products (name,type,sales_price,cost_price,notes) VALUES
 ('Brightloop CRM','software',1000.00,400.00,'Demo product'),
 ('Brightloop Support','service',500.00,200.00,'Demo product');
INSERT INTO product_variants (product_id,name,extra_price) VALUES
 (1,'Basic',0),(1,'Odoo',560),(1,'Premium',1000);
INSERT INTO plans (name,product_id,price,billing_frequency,min_quantity,allow_early_close,allow_pause,allow_renewal)
 VALUES ('CRM Monthly',1,1000.00,'monthly',1,1,1,1),('CRM Yearly',1,10000.00,'yearly',1,1,1,1),('Support Weekly',2,500.00,'weekly',1,0,1,1);
