# api-platform-extras

Configuration (config/packages/api_platform_extras.yaml):

```yaml
api_platform_extras:
  features:
      http_cache: { enabled: false }
      schema_decoration: { enabled: false }
      simple_normalizer: { enabled: false }
      jwt_refresh: { enabled: false }
      iri_template_generator: { enabled: false }
      schema_processor: { enabled: false }
```

Enable features by setting the corresponding flag to true.
