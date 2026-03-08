# Enterprise SaaS Multi-Tenant Inventory Management System

A production-ready, enterprise-grade microservice-driven Inventory Management System built with **Laravel 11**, demonstrating **Domain-Driven Design (DDD)**, the **Saga Pattern** for distributed transactions, and a fully dynamic **multi-tenant SaaS architecture**.

---

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                          API Gateway (:8080)                        │
│              Rate Limiting · Auth Validation · Routing              │
└───────────┬────────────────────────────────────────────────────────┘
            │
┌───────────▼──────────────────────────────────────────────────────────┐
│                         Microservices                                │
│                                                                      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐   │
│  │ Auth Service │  │Tenant Service│  │   Inventory Service       │   │
│  │   :8081      │  │   :8082      │  │       :8083               │   │
│  │              │  │              │  │                           │   │
│  │ Passport SSO │  │ Multi-tenant │  │  Products/Categories/     │   │
│  │ RBAC/ABAC    │  │ Management   │  │  Warehouses/Stock Mgmt    │   │
│  └──────────────┘  └──────────────┘  └──────────────────────────┘   │
│                                                                      │
│  ┌──────────────┐  ┌──────────────────┐  ┌────────────────────────┐ │
│  │ Order Service│  │  Notification    │  │  Saga Orchestrator     │ │
│  │   :8084      │  │  Service :8085   │  │      :8086             │ │
│  │              │  │                  │  │                        │ │
│  │ Saga Pattern │  │ Email/Webhook/   │  │ Distributed Txn Audit  │ │
│  │ Stock Reserv │  │ In-App Notifs    │  │ Compensation Tracking  │ │
│  └──────────────┘  └──────────────────┘  └────────────────────────┘ │
└──────────────────────────────────────────────────────────────────────┘
            │
┌───────────▼──────────────────────────────────────────────────────────┐
│                      Infrastructure                                  │
│  MySQL 8.0 · Redis 7 · RabbitMQ 3.12 · Apache Kafka 7.5             │
└──────────────────────────────────────────────────────────────────────┘
```

---

## Key Design Principles

| Principle | Implementation |
|-----------|---------------|
| **DDD** | Domain models, aggregates, value objects, domain events, bounded contexts |
| **SOLID** | Interfaces for all repositories and services; dependency injection throughout |
| **Clean Architecture** | Controller → Service → Repository; thin controllers, fat services |
| **Saga Pattern** | `SagaOrchestrator` with `SagaStep` (execute + compensate) for distributed transactions |
| **Multi-Tenancy** | `TenantContext` + `TenantResolverMiddleware`; all repositories are tenant-scoped |
| **RBAC + ABAC** | Spatie Permissions (RBAC) + `PolicyRegistry` with named ABAC policies |
| **DRY** | `BaseRepository` with `CanFilter`, `CanSearch`, `CanSort`, `CanPaginate` traits |
| **Pipeline** | `PipelineInterface` / `LaravelPipeline` wrapping Laravel's built-in pipeline |

---

## Microservices

| Service | Port | Database | Description |
|---------|------|----------|-------------|
| API Gateway | 8080 | Redis | Rate limiting, auth proxy, service routing |
| Auth Service | 8081 | saas_auth | Laravel Passport SSO, multi-guard auth, user management |
| Tenant Service | 8082 | saas_tenant | Tenant CRUD, webhooks, runtime config |
| Inventory Service | 8083 | saas_inventory | Products, categories, warehouses, stock management |
| Order Service | 8084 | saas_orders | Order processing with Saga orchestration |
| Notification Service | 8085 | saas_notifications | Email, webhook, in-app notifications |
| Saga Orchestrator | 8086 | saas_saga | Distributed transaction audit and replay |

---

## Quick Start

### Prerequisites
- Docker 24+
- Docker Compose 2.20+

### 1. Clone and configure environment

```bash
# Copy environment files for each service
for svc in api-gateway auth-service tenant-service inventory-service order-service notification-service saga-orchestrator; do
    cp services/$svc/.env.example services/$svc/.env
done
```

### 2. Start infrastructure and services

```bash
docker-compose up -d
```

### 3. Run database migrations

```bash
# Run migrations for each service
for svc in auth-service tenant-service inventory-service order-service notification-service saga-orchestrator; do
    docker-compose exec $svc php artisan migrate --force
done
```

### 4. Verify all services are healthy

```bash
curl http://localhost:8080/health
```

---

## Shared Kernel

Located in `packages/shared-kernel/`, this library provides:

### Domain Building Blocks
- **Value Objects**: `TenantId`, `UserId`, `Money`, `Email`, `Quantity`
- **AggregateRoot**: Collects and dispatches domain events
- **DomainEvent**: Base class with full envelope for message broker transport

### Base Repository
The `BaseRepository` class supports:
```php
// Conditional pagination: returns Collection when per_page absent, Paginator otherwise
$products = $repository->findAll([
    'per_page'  => 15,    // optional – omit for all results
    'page'      => 1,
    'sort_by'   => 'name',
    'sort_dir'  => 'asc',
    'search'    => 'laptop',
    'filters'   => [
        'status'     => 'active',
        'created_at' => ['operator' => 'between', 'value' => ['2024-01-01', '2024-12-31']],
    ],
    'with'      => ['category'],
    'scopes'    => ['active' => []],
]);
```

### Saga Orchestrator
```php
$steps = [
    new SagaStep(
        name:      'reserve-stock',
        execute:   fn($ctx) => $this->inventoryService->reserve($ctx['product_id'], $ctx['quantity']),
        compensate: fn($ctx) => $this->inventoryService->release($ctx['product_id'], $ctx['quantity'])
    ),
    // ... more steps
];

$result = $sagaOrchestrator->run($steps, ['product_id' => '...', 'quantity' => 5]);
```

### Message Broker
```php
// Pluggable via config/broker.php:
// MESSAGE_BROKER_DRIVER=rabbitmq|kafka|sync

$broker->publish('inventory.product.created', ['product_id' => '...']);
$broker->subscribe('inventory.product.created', fn($msg) => handleEvent($msg));
```

---

## API Reference

### Authentication
```
POST /api/v1/auth/register    Register a new user
POST /api/v1/auth/login       Login and receive access token
POST /api/v1/auth/logout      Revoke token (requires Bearer token)
GET  /api/v1/auth/me          Get current user
```

### Inventory
```
GET    /api/v1/products                  List products (supports filtering, search, pagination)
POST   /api/v1/products                  Create a product
GET    /api/v1/products/{id}             Get product
PUT    /api/v1/products/{id}             Update product
DELETE /api/v1/products/{id}             Soft-delete product
POST   /api/v1/products/{id}/adjust-stock Adjust stock level

GET    /api/v1/categories                List categories
POST   /api/v1/categories                Create category
GET    /api/v1/categories/{id}           Get category
PUT    /api/v1/categories/{id}           Update category
DELETE /api/v1/categories/{id}           Delete category

GET    /api/v1/warehouses                List warehouses
POST   /api/v1/warehouses                Create warehouse
GET    /api/v1/stock-movements           List stock movements (audit trail)
```

### Orders
```
GET    /api/v1/orders                    List orders
POST   /api/v1/orders                    Create order (triggers Saga)
GET    /api/v1/orders/{id}               Get order
POST   /api/v1/orders/{id}/cancel        Cancel order
```

### Tenants
```
GET    /api/v1/tenants                   List tenants
POST   /api/v1/tenants                   Create tenant
GET    /api/v1/tenants/{id}              Get tenant
PUT    /api/v1/tenants/{id}              Update tenant
PUT    /api/v1/tenants/{id}/config       Update runtime config
```

### Health Checks
```
GET /health    Service health (available on every microservice)
```

---

## Multi-Tenancy

Each request must include `X-Tenant-ID` header:
```bash
curl -H "X-Tenant-ID: tenant-uuid" \
     -H "Authorization: Bearer <token>" \
     http://localhost:8080/api/v1/products
```

All repository queries are automatically scoped to the current tenant via `TenantAwareRepository`.

---

## Distributed Transaction (Saga) Example

Creating an order triggers a 4-step Saga:
```
1. create-order-record    → compensate: delete order
2. reserve-inventory-stock → compensate: release stock
3. confirm-order          → compensate: revert to pending
4. publish-order-event    → compensate: publish order.cancelled
```

If any step fails, all previously completed steps are compensated in reverse order, guaranteeing eventual consistency.

---

## Runtime Configuration

Update tenant config at runtime without restarting:
```bash
PUT /api/v1/tenants/{id}/config
{
  "config": {
    "mail.mailer": "mailgun",
    "mail.host": "smtp.mailgun.org",
    "cache.default": "redis"
  }
}
```

---

## Testing

```bash
# Shared kernel unit tests
cd packages/shared-kernel && composer install && vendor/bin/phpunit

# Inventory service feature tests
cd services/inventory-service && composer install && vendor/bin/phpunit

# Individual service tests
cd services/auth-service && vendor/bin/phpunit
```

---

## Security

- **Authentication**: Stateless Bearer tokens via Laravel Passport OAuth2
- **Multi-tenancy**: Strict tenant isolation at repository layer (`TenantAwareRepository`)
- **RBAC**: Role-based access via Spatie Permissions (per-tenant)
- **ABAC**: Attribute-based policies via `PolicyRegistry` (`TenantIsolationPolicy`, `OwnershipPolicy`)
- **Webhooks**: HMAC-SHA256 signed outbound webhooks
- **Rate Limiting**: Per-tenant sliding window at API Gateway
- **Token Caching**: Gateway caches token validation to reduce auth service load
- **SQL Injection**: All queries via Eloquent parameterized queries

---

## Project Structure

```
├── packages/
│   └── shared-kernel/           # DDD building blocks, base repository, saga, broker
├── services/
│   ├── api-gateway/             # Auth proxy, rate limiting, service routing
│   ├── auth-service/            # Laravel Passport SSO
│   ├── tenant-service/          # Multi-tenant management
│   ├── inventory-service/       # Core inventory (products, warehouses, stock)
│   ├── order-service/           # Order processing + Saga
│   ├── notification-service/    # Email, webhook, in-app notifications
│   └── saga-orchestrator/       # Distributed transaction audit
├── docker/                      # Nginx, PHP, supervisord configs
└── docker-compose.yml           # Full stack orchestration
``` 
