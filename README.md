# api-platform-extras

Configuration (config/packages/api_platform_extras.yaml):

```yaml
api_platform_extras:
  features:
    http_cache:
      enabled: false
    schema_decoration:
      enabled: false
      #Mark schema properties as required by default when the type is not nullable.
      default_required_properties: false
      #Add @id as an optional property to all POST, PUT and PATCH schemas.
      jsonld_update_schema: false
    simple_normalizer:
      enabled: false
    jwt_refresh:
      enabled: false
      auto_refresh_cookie: false
      auto_refresh_header: false
      ignored_routes: []
      ignored_paths: []
      allowed_firewalls: []
    iri_template_generator:
      enabled: false
    schema_processor:
      enabled: false
```

Enable features by setting the corresponding flag to true.

## JWT Refresh Feature

`jwt_refresh` is active only when:

- `api_platform_extras.features.jwt_refresh.enabled: true`
- at least one of:
  - `api_platform_extras.features.jwt_refresh.auto_refresh_cookie: true`
  - `api_platform_extras.features.jwt_refresh.auto_refresh_header: true`

If both auto-refresh flags are `false`, behavior is effectively the same as feature disabled.

### Related bundle config

JWT/refresh token names and header prefix are taken from Lexik/Gesdinet config (with bundle defaults):

- `lexik_jwt_authentication.token_extractors.authorization_header.prefix` (default: `Bearer`)
- `lexik_jwt_authentication.token_extractors.authorization_header.name` (default: `Authorization`)
- `lexik_jwt_authentication.token_extractors.cookie.name` (default: `BEARER`)
- `gesdinet_jwt_refresh_token.token_parameter_name` (default: `refresh_token`)

When Lexik extractor parameters are not exposed as container parameters, values are read from Lexik extractor service definition arguments.
