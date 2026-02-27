# api-platform-extras

Configuration (config/packages/api_platform_extras.yaml):

```yaml
api_platform_extras:
  features:
    # NOT IMPLEMENTED YET
    http_cache:
      enabled: false
    schema_decoration:
      enabled: false
      #Mark schema properties as required by default when the type is not nullable.
      default_required_properties: false
      #Additionally mark nullable types as required - safe to use if api_platform.defaults.normalization_context.skip_null_values set to false (default true).
      nullable_required: false
      #Add @id as an optional property to all POST, PUT and PATCH schemas.
      jsonld_update_schema: false
    hydra_pagination_enrichment:
      #Adds numeric pagination fields to Hydra view keys (prefix depends on api_platform.serializer.hydra_prefix).
      enabled: false
    # NOT IMPLEMENTED YET
    simple_normalizer:
      enabled: false
    jwt_refresh:
      enabled: false
      auto_refresh_cookie: false
      auto_refresh_header: false
      user_aware: false
      ignored_routes: []
      ignored_paths: []
      allowed_firewalls: []
    iri_template_generator:
      enabled: false
    schema_processor:
      enabled: false
```

Enable features by setting the corresponding flag to true.

## Hydra Pagination Enrichment Feature

`hydra_pagination_enrichment` adds numeric pagination fields (`firstPage`, `lastPage`, `currentPage`, `previousPage`, `nextPage`, `itemsPerPage`) to Hydra collection view in both schema and response.

The Hydra key prefix is controlled by API Platform and is boolean:

- `api_platform.serializer.hydra_prefix: true` -> prefixed keys (for example `hydra:view`, `hydra:first`)
- `api_platform.serializer.hydra_prefix: false` (default) -> unprefixed keys (`view`, `first`)

## JWT Refresh Feature

`jwt_refresh` is active only when:

- `api_platform_extras.features.jwt_refresh.enabled: true`
- at least one of:
  - `api_platform_extras.features.jwt_refresh.auto_refresh_cookie: true`
  - `api_platform_extras.features.jwt_refresh.auto_refresh_header: true`

If both auto-refresh flags are `false`, behavior is effectively the same as feature disabled.

`user_aware` defaults to `false`. When enabled, refresh token handling validates that the selected user provider supports the user class stored on the refresh token.

### Related bundle config

JWT/refresh token names and header prefix are taken from Lexik/Gesdinet config (with bundle defaults):

- `lexik_jwt_authentication.token_extractors.authorization_header.prefix` (default: `Bearer`)
- `lexik_jwt_authentication.token_extractors.authorization_header.name` (default: `Authorization`)
- `lexik_jwt_authentication.token_extractors.cookie.name` (default: `BEARER`)
- `gesdinet_jwt_refresh_token.token_parameter_name` (default: `refresh_token`)

When Lexik extractor parameters are not exposed as container parameters, values are read from Lexik extractor service definition arguments.

### Refresh token entity

When using custom refresh token entities, extend the bundle entity:

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'project_prefix_refresh_token')]
class RefreshToken extends \Netgen\ApiPlatformExtras\Entity\RefreshToken {}
```

```yaml
# config/doctrine/RefreshToken.orm.yaml
App\Entity\RefreshToken:
  type: entity
  table: project_prefix_refresh_token
```

```xml
<!-- config/doctrine/RefreshToken.orm.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                                      https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">
    <entity name="App\Entity\RefreshToken" table="project_prefix_refresh_token" />
</doctrine-mapping>
```

And configure Gesdinet to use your entity:

```yaml
gesdinet_jwt_refresh_token:
  refresh_token_class: App\Entity\RefreshToken
```

## Logout Configuration

Recommended config to invalidate both tokens and clear cookies with no custom app logic:

```yaml
# config/packages/lexik_jwt_authentication.yaml
lexik_jwt_authentication:
  blocklist_token:
    enabled: true
```

```yaml
# config/packages/security.yaml
security:
  firewalls:
    api:
      logout:
        path: app_logout
        delete_cookies:
          # JWT cookie configured in lexik_jwt_authentication.token_extractors.cookie.name
          jwt-bearer: ~
          # Refresh cookie configured in gesdinet_jwt_refresh_token.token_parameter_name
          refresh-token: ~
      refresh-jwt:
        invalidate_token_on_logout: true
```

Notes:

- `invalidate_token_on_logout: true` (Gesdinet) deletes refresh token on logout.
- `blocklist_token.enabled: true` (Lexik) blacklists JWT on logout.
- This bundle normalizes Gesdinet `400 No refresh_token found.` to `200 Logged out.` for idempotent logout responses.
