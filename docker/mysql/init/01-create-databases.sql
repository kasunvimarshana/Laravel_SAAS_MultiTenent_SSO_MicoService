-- Create databases for each microservice
CREATE DATABASE IF NOT EXISTS `saas_auth` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `saas_tenant` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `saas_inventory` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `saas_orders` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `saas_notifications` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `saas_saga` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Grant permissions
GRANT ALL PRIVILEGES ON `saas_auth`.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON `saas_tenant`.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON `saas_inventory`.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON `saas_orders`.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON `saas_notifications`.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON `saas_saga`.* TO 'root'@'%';
FLUSH PRIVILEGES;
