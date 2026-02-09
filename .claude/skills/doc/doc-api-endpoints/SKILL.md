# Command: doc-api-endpoints
Generate comprehensive API endpoint documentation with interactive examples

## 🚨 CRITICAL OUTPUT PATH CONFIGURATION

**ALL API documentation MUST be written to the `docs/api/` directory structure.**

### Standard API Documentation Paths
- **API Hub**: `docs/api/README.md` - Main API documentation index
- **Endpoints**: `docs/api/endpoints/` - Individual endpoint documentation
- **Schemas**: `docs/api/schemas/` - Data model documentation
- **Examples**: `docs/api/examples/` - Usage examples and integration guides

Refer to `templates/shared/documentation-patterns.md` for complete path specifications.

## Usage
```
/doc-api-endpoints [endpoint-path|file|directory]
```

## Description
Creates professional API documentation including OpenAPI/Swagger specs, request/response examples, error codes, and interactive testing interfaces. Follows industry best practices from FastAPI, Stripe, and Twilio.

## Implementation

### Core Documentation Template
```xml
<instructions>
Document this REST API endpoint comprehensively
</instructions>

<endpoint>
Method: {{http_method}}
Path: {{endpoint_path}}
Authentication: {{auth_type|Bearer token}}
</endpoint>

<context>
API version: {{api_version|v1}}
Target audience: {{audience|Backend developers}}
Documentation style: {{style|Technical reference with examples}}
Include: OpenAPI 3.0 specification
</context>

<output_format>
File: docs/api/endpoints/{endpoint-name}.md

1. Endpoint Overview
   - Purpose and use cases
   - Authentication requirements
   - Rate limiting information
   
2. Request Format
   - Path parameters with types
   - Query parameters with defaults
   - Request body schema
   - Required vs optional fields
   
3. Response Format
   - Success response schema
   - Response codes and meanings
   - Response headers
   - Pagination details (if applicable)
   
4. Error Handling
   - Error response format
   - Common error codes
   - Troubleshooting guide
   
5. Code Examples
   - curl command
   - JavaScript/fetch
   - Python/requests
   - Go/net/http
   - Response examples
   
6. Testing Interface
   - Interactive API explorer
   - Sample payloads
   - Authentication setup
   
Additional files:
- docs/api/README.md - API overview and navigation
- docs/api/schemas/{model}.md - Data model documentation
- docs/api/examples/integration-guide.md - Integration examples
</output_format>
```

### Endpoint Analysis Phase
```xml
<instructions>
Analyze API endpoint implementation to extract:
</instructions>

<analysis_targets>
- HTTP methods and routes
- Request/response data models
- Validation rules and constraints
- Authentication/authorization logic
- Error handling patterns
- Rate limiting rules
- Database queries and performance
</analysis_targets>

<code_context>
{{endpoint_implementation}}
</code_context>

<output>
JSON structure with extracted endpoint metadata
</output>
```

### OpenAPI Specification Generation
```yaml
openapi: 3.0.0
info:
  title: {{api_title}}
  version: {{api_version}}
  description: {{api_description}}
  
servers:
  - url: {{base_url}}
    description: {{environment}}

paths:
  {{endpoint_path}}:
    {{http_method}}:
      summary: {{endpoint_summary}}
      description: {{detailed_description}}
      operationId: {{operation_id}}
      tags:
        - {{resource_tag}}
      
      parameters:
        - name: {{param_name}}
          in: {{param_location|path|query|header}}
          required: {{required|true|false}}
          description: {{param_description}}
          schema:
            type: {{param_type}}
            format: {{param_format}}
            default: {{default_value}}
            
      requestBody:
        required: {{body_required}}
        content:
          application/json:
            schema:
              $ref: '#/components/schemas/{{schema_name}}'
            examples:
              {{example_name}}:
                value: {{example_json}}
                
      responses:
        '200':
          description: {{success_description}}
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/{{response_schema}}'
              examples:
                success:
                  value: {{success_example}}
        '400':
          description: Bad Request
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/Error'
        '401':
          description: Unauthorized
        '404':
          description: Not Found
        '500':
          description: Internal Server Error
```

### Code Example Templates

#### cURL Example
```bash
curl -X {{METHOD}} '{{BASE_URL}}{{ENDPOINT_PATH}}' \
  -H 'Authorization: Bearer {{API_KEY}}' \
  -H 'Content-Type: application/json' \
  -d '{{REQUEST_BODY_JSON}}'
```

#### JavaScript/Fetch Example
```javascript
const response = await fetch('{{BASE_URL}}{{ENDPOINT_PATH}}', {
  method: '{{METHOD}}',
  headers: {
    'Authorization': 'Bearer {{API_KEY}}',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({{REQUEST_BODY}})
});

const data = await response.json();
console.log(data);
```

#### Python/Requests Example
```python
import requests

url = '{{BASE_URL}}{{ENDPOINT_PATH}}'
headers = {
    'Authorization': 'Bearer {{API_KEY}}',
    'Content-Type': 'application/json'
}
payload = {{REQUEST_BODY}}

response = requests.{{method_lower}}(url, json=payload, headers=headers)
data = response.json()
print(data)
```

## Advanced Features

### Batch Endpoint Documentation
Process multiple endpoints simultaneously:
```
/doc-api-endpoints ./controllers/ --batch
```

**Output Structure:**
```
docs/api/
├── README.md                    # API overview and navigation
├── endpoints/                   # Individual endpoint docs
│   ├── users.md                # Users API endpoints
│   ├── orders.md               # Orders API endpoints
│   └── payments.md             # Payments API endpoints
├── schemas/                     # Data models
│   ├── User.md
│   ├── Order.md
│   └── Payment.md
└── examples/
    ├── authentication.md        # Auth examples
    ├── pagination.md           # Pagination examples
    └── error-handling.md       # Error handling examples
```

### GraphQL Documentation
```xml
<instructions>
Document GraphQL schema and operations
</instructions>

<schema>
{{graphql_schema}}
</schema>

<output_format>
- Type definitions with descriptions
- Query documentation with arguments
- Mutation documentation with inputs
- Subscription documentation
- Example queries and responses
- Error handling patterns
</output_format>
```

### WebSocket Documentation
```xml
<instructions>
Document WebSocket endpoints and message formats
</instructions>

<websocket>
Endpoint: {{ws_endpoint}}
Protocol: {{ws_protocol}}
Message formats: {{message_types}}
</websocket>

<output_format>
- Connection establishment
- Message types and formats
- Event sequences
- Error conditions
- Client implementation examples
</output_format>
```

## Integration Options

### Postman Collection Generation
```json
{
  "info": {
    "name": "{{api_name}} Collection",
    "description": "Auto-generated from API documentation"
  },
  "item": [
    {
      "name": "{{endpoint_name}}",
      "request": {
        "method": "{{method}}",
        "url": "{{url}}",
        "header": {{headers}},
        "body": {{body}}
      }
    }
  ]
}
```

### API Testing Integration
- Generate test cases for each endpoint
- Include positive and negative test scenarios
- Validate response schemas
- Performance benchmarks

## Output Examples

### REST API Documentation

**File: `docs/api/endpoints/users.md`**

```markdown
## GET /api/v1/users/{userId}

Retrieves detailed information about a specific user.

### Authentication
🔒 Requires Bearer token

### Parameters

| Name | Type | In | Required | Description |
|------|------|-----|----------|-------------|
| userId | string | path | Yes | Unique user identifier |
| include | string | query | No | Comma-separated list of related data to include |

### Response

#### Success Response (200 OK)
```json
{
  "id": "usr_123abc",
  "email": "user@example.com",
  "name": "John Doe",
  "created_at": "2024-01-15T10:30:00Z"
}
```

#### Error Responses
- `404 Not Found` - User does not exist
- `401 Unauthorized` - Invalid or missing authentication

### Related Documentation
- [User Schema](../schemas/User.md)
- [Authentication Guide](../examples/authentication.md)
- [API Overview](../README.md)
```

## Quality Metrics
✅ All endpoints have complete documentation
✅ Every parameter is described with type and constraints
✅ All error conditions are documented
✅ Code examples are tested and working
✅ Documentation matches implementation 100%