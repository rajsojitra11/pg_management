# Modules Detailed

| Module | Purpose | Tables Used | Controllers Used | Services Used | Routes Used | Known Incomplete Logic |
| --- | --- | --- | --- | --- | --- | --- |
| Login | Auth/login/logout | users, sessions | AuthenticatedSessionController, LoginController | Laravel auth | web/api | Password reset route ownership is intentionally minimal |
| Dashbord | Dashboard and widget config | dashboard_widgets, role_dashboard_configs, user_dashboard_configs plus domain reads | DashbordController | DashboardService, PrintDashboardService | web/api | KPI logic should be checked before report changes |
| PgManagement | PG master | pg_management, pg_management_logs | PgManagementController | none explicit | web/api | No capacity enforcement by itself |
| Room | Room categories and rooms | pg_room_categories, pg_rooms, logs | RoomCategoryController, RoomController | none explicit | web/api | Capacity/occupancy lock not globally enforced |
| Tenant | Tenant onboarding | tenants, users, user_profile, tenant_logs | TenantController | none explicit | web/api | Multi-table create must remain transactional |
| Payment | Payments and verification | payments, payment_logs | PaymentController | none explicit | web/api | Verified payment lock rules need explicit enforcement |
| Complaint | Complaints | complaints, complaint_logs, services | ComplaintController | none explicit | web/api | No full workflow state machine found |
| Maintenance | Maintenance records | maintenances, maintenance_logs | MaintenanceController | none explicit | web/api | No work-order state machine found |
| Service | Service categories/services | service_categories, services, logs | ServiceCategoryController, ServiceController | none explicit | web/api | Category-service consistency must be validated |
| Subscription | Subscriptions | subscriptions, subscription_logs | SubscriptionController | none explicit | web/api | Billing integration not evident |
| User | Users/profile/preferences | users, user_profile, user_preferences, user_logs | UserController | auth/profile helpers | web/api | User/tenant overlap requires care |
| Role | Roles and permissions | roles, permissions, role_year_accesses, role_logs | RoleController | Spatie Permission | web/api | Matrix is DB/seed driven |
| MenuMaster | Dynamic menus | menu_masters, menu_master_logs | MenuMasterController | MenuMasterService | web/api | Tree order/hierarchy sensitive |
| Setting | Company/app settings | settings, setting_logs | SettingController | helper/config | web/api | File upload cleanup should be reviewed |
| Email | Email config/templates/reminders | email_configs, email_templates, logs | EmailController | mail command | web/api | SMTP validation and failures need tests |
| EnvVariable | Env management | env_variables, env_variable_logs | EnvVariableController | EnvFileService | web/api | High-risk production feature |
| Master data | Country, State, City, Currency, Unit, Year | respective tables/logs | respective controllers | none explicit | web/api | Mostly CRUD |
| Noticeboard | Notices | noticeboards, noticeboard_logs | NoticeboardController | none explicit | web/api | Audience targeting unclear |
