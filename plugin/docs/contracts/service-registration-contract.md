# Service Registration Contract

**Document type:** Authoritative contract for module registration and service wiring.  
**Governs:** Bootstrap wiring, provider implementations, and service ID usage.  
**Reference:** Master Specification §5.1, §5.2, §5.3, §5.6, §57.2, §57.8, §59.3.

---

## 1. Provider responsibilities

- **Service_Provider_Interface** has a single method: `register( Service_Container $container ): void`. Implementations bind service IDs to factories on the container. No other contract is imposed.
- Providers run only during bootstrap (server-side). Registration is not request-driven and must not depend on user input.
- Each provider is responsible for one or more related services. Bootstrap-level providers register identity, config, diagnostics placeholder, admin router placeholder, and capability placeholder. Domain providers (crawler, AI, build plan, execution, etc.) will be added in later prompts and isolated by domain.

---

## 2. Service ID naming pattern

- Use **lowercase** and **snake_case**.
- IDs are **stable** and **machine-readable**. Do not rename IDs that are already in use; introduce new IDs for new services.
- No user-controlled or request-derived IDs. IDs are fixed strings in code only.
- Current bootstrap IDs (do not rename):

  | ID             | Purpose                              |
  |----------------|--------------------------------------|
  | `config`       | Constants-aware config (Constants + Versions). |
  | `diagnostics`  | Diagnostics/logging bootstrap (placeholder).    |
  | `admin_router` | Admin menu/screen routing (placeholder).        |
  | `capabilities` | Capability registration (placeholder).         |

- Future domain services will use the same pattern (e.g. `registry`, `crawler`, `ai_provider`, `build_plan`, `executor`). Document new IDs in this contract when added.

---

## 3. Bootstrap vs domain registration

- **Bootstrap registration** happens in `Module_Registrar::register_bootstrap()`, in a fixed order: config, diagnostics, admin_router, capabilities. Only bootstrap-level providers run there. No crawler, AI, build plan, or execution services are registered in the bootstrap phase.
- **Domain registration** will be added in later prompts. Domain providers must be registered through the same container and registrar pattern (explicit registration order, no magic). Planner vs executor separation is preserved by isolating provider responsibilities by domain.
- Domain code must not reach into unrelated domains. Services receive explicit dependencies; no service-locator pattern in domain code.

---

## 4. Container behavior

- **Service_Container** supports `register( string $id, callable $factory )` and `get( string $id )`. Resolved instances are cached (singleton per ID). Missing IDs throw a clear exception.
- No autowiring, no reflection, no external DI framework. Factories are plain callables.
- The container is not exposed as a global or static mutable accessor. Bootstrap (e.g. `Plugin::run()`) creates the container and registrar and may hold the container in a contained scope for future hook wiring only.

---

## 5. Prohibition on direct domain instantiation in root file

- The root plugin file (`aio-page-builder.php`) must only: guard against direct access, load Constants, call `Constants::init()`, load Plugin, and register activation/deactivation/plugins_loaded hooks. It must **not** instantiate domain services, storage classes, registries, or any business-logic class. All wiring goes through the bootstrap class and the registrar.

---

## 6. Security

- Service registration is server-side only. No request-driven class loading. No user-controlled service IDs. No dynamic include paths based on input.
