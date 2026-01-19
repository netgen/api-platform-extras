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
    iri_template_generator:
      enabled: false
    schema_processor:
      enabled: false
```

Enable features by setting the corresponding flag to true.
