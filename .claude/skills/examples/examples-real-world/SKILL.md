# Command: examples-real-world
Generate production-ready examples based on real-world scenarios and best practices

## Usage
```
/examples-real-world [domain] --scenario [api|cli|data|web|ml|devops]
```

## Description
Creates comprehensive, production-quality examples that solve real business problems. Each example includes error handling, logging, monitoring, security, performance optimization, and deployment considerations.

## Documentation Structure Compliance
Real-world examples are organized in `docs/` for production deployment:

### Production Organization
- **Production Projects**: See `/templates/resources/documentation-library/core/structure-manager.md`
  - Scenarios in: `docs/deployment/examples/` or `docs/operations/runbooks/`
  - Integration guides: `docs/user-guides/integration/`
- **Single Libraries**: See `/templates/resources/documentation-library/core/structure-manager.md`
  - Real-world recipes: `docs/examples/recipes/`
- **Aggregated Libraries**: See `/templates/resources/documentation-library/core/structure-manager.md`
  - Cross-module scenarios: `docs/recipes/` and `docs/patterns/`

Production examples include deployment configs, monitoring setup, and operational guides.

## Implementation

### Real-World Scenario Framework
```xml
<instructions>
Generate production-ready examples for actual use cases
</instructions>

<scenario_requirements>
- Solve real business problems
- Include production concerns
- Handle scale and performance
- Implement security best practices
- Provide deployment guidance
- Include monitoring and observability
- Document operational considerations
</scenario_requirements>

<production_features>
- Configuration management
- Environment variables
- Logging and monitoring
- Error recovery
- Rate limiting
- Caching strategies
- Database transactions
- API versioning
- Authentication/Authorization
- Health checks
</production_features>
```

## Domain-Specific Scenarios

### 🌐 Web API Scenario
**Production REST API with all enterprise features**

```python
"""
Real-World Scenario: E-commerce Order Processing API
Production-ready implementation with all enterprise features
"""

import os
import logging
import time
from datetime import datetime, timedelta
from typing import Optional, Dict, Any, List
from functools import wraps
from dataclasses import dataclass, asdict
import hashlib
import hmac
import json

from flask import Flask, request, jsonify, g
from flask_limiter import Limiter
from flask_cors import CORS
from prometheus_client import Counter, Histogram, generate_latest
import redis
import jwt
from sqlalchemy import create_engine, Column, String, Float, DateTime, Enum
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import sessionmaker, scoped_session
from sqlalchemy.exc import IntegrityError
import sentry_sdk
from sentry_sdk.integrations.flask import FlaskIntegration

# Production configuration
class Config:
    """Production configuration with environment variables"""
    SECRET_KEY = os.environ.get('SECRET_KEY', 'change-in-production')
    DATABASE_URL = os.environ.get('DATABASE_URL', 'postgresql://user:pass@localhost/db')
    REDIS_URL = os.environ.get('REDIS_URL', 'redis://localhost:6379')
    SENTRY_DSN = os.environ.get('SENTRY_DSN')
    JWT_EXPIRY_HOURS = int(os.environ.get('JWT_EXPIRY_HOURS', '24'))
    RATE_LIMIT_PER_MINUTE = int(os.environ.get('RATE_LIMIT_PER_MINUTE', '60'))
    LOG_LEVEL = os.environ.get('LOG_LEVEL', 'INFO')
    ENVIRONMENT = os.environ.get('ENVIRONMENT', 'development')

# Initialize monitoring
sentry_sdk.init(
    dsn=Config.SENTRY_DSN,
    integrations=[FlaskIntegration()],
    traces_sample_rate=0.1,
    environment=Config.ENVIRONMENT
)

# Logging configuration
logging.basicConfig(
    level=getattr(logging, Config.LOG_LEVEL),
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.StreamHandler(),
        logging.FileHandler('app.log')
    ]
)
logger = logging.getLogger(__name__)

# Metrics
order_counter = Counter('orders_total', 'Total orders processed')
order_histogram = Histogram('order_processing_seconds', 'Order processing time')
error_counter = Counter('errors_total', 'Total errors', ['error_type'])

# Database setup
Base = declarative_base()
engine = create_engine(Config.DATABASE_URL, pool_size=20, max_overflow=40)
Session = scoped_session(sessionmaker(bind=engine))

class Order(Base):
    """Order model with proper constraints"""
    __tablename__ = 'orders'
    
    id = Column(String(36), primary_key=True)
    user_id = Column(String(36), nullable=False, index=True)
    product_id = Column(String(36), nullable=False)
    quantity = Column(Float, nullable=False)
    total_amount = Column(Float, nullable=False)
    status = Column(Enum('pending', 'processing', 'completed', 'failed'), default='pending')
    created_at = Column(DateTime, default=datetime.utcnow, index=True)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

# Initialize Flask app
app = Flask(__name__)
app.config.from_object(Config)
CORS(app, origins=os.environ.get('ALLOWED_ORIGINS', '*').split(','))

# Initialize Redis for caching
redis_client = redis.from_url(Config.REDIS_URL, decode_responses=True)

# Rate limiting
limiter = Limiter(
    app,
    key_func=lambda: get_user_id() or get_remote_address(),
    storage_uri=Config.REDIS_URL
)

def get_user_id():
    """Get user ID from JWT token"""
    return g.get('user_id')

def get_remote_address():
    """Get client IP address"""
    return request.environ.get('HTTP_X_FORWARDED_FOR', request.remote_addr)

# Authentication decorator
def require_auth(f):
    """JWT authentication decorator"""
    @wraps(f)
    def decorated(*args, **kwargs):
        token = request.headers.get('Authorization', '').replace('Bearer ', '')
        
        if not token:
            logger.warning(f"Missing auth token from {get_remote_address()}")
            return jsonify({'error': 'Authentication required'}), 401
        
        try:
            payload = jwt.decode(token, Config.SECRET_KEY, algorithms=['HS256'])
            g.user_id = payload['user_id']
            g.user_role = payload.get('role', 'user')
        except jwt.ExpiredSignatureError:
            error_counter.labels('auth_expired').inc()
            return jsonify({'error': 'Token expired'}), 401
        except jwt.InvalidTokenError as e:
            error_counter.labels('auth_invalid').inc()
            logger.error(f"Invalid token: {e}")
            return jsonify({'error': 'Invalid token'}), 401
        
        return f(*args, **kwargs)
    return decorated

# Request ID middleware
@app.before_request
def before_request():
    """Add request ID for tracing"""
    g.request_id = request.headers.get('X-Request-ID', generate_request_id())
    g.start_time = time.time()
    logger.info(f"Request started: {request.method} {request.path} [{g.request_id}]")

@app.after_request
def after_request(response):
    """Log request completion and add headers"""
    if hasattr(g, 'request_id'):
        response.headers['X-Request-ID'] = g.request_id
    
    if hasattr(g, 'start_time'):
        elapsed = time.time() - g.start_time
        response.headers['X-Response-Time'] = f"{elapsed:.3f}"
        logger.info(f"Request completed: {request.method} {request.path} "
                   f"[{response.status_code}] [{elapsed:.3f}s] [{g.request_id}]")
    
    return response

def generate_request_id():
    """Generate unique request ID"""
    return hashlib.md5(f"{time.time()}{os.urandom(16)}".encode()).hexdigest()

# Health checks
@app.route('/health')
def health_check():
    """Basic health check"""
    return jsonify({'status': 'healthy', 'timestamp': datetime.utcnow().isoformat()})

@app.route('/health/ready')
def readiness_check():
    """Readiness probe for Kubernetes"""
    checks = {
        'database': check_database(),
        'redis': check_redis(),
    }
    
    if all(checks.values()):
        return jsonify({'status': 'ready', 'checks': checks})
    else:
        return jsonify({'status': 'not ready', 'checks': checks}), 503

def check_database():
    """Check database connectivity"""
    try:
        Session.execute('SELECT 1')
        return True
    except Exception as e:
        logger.error(f"Database check failed: {e}")
        return False

def check_redis():
    """Check Redis connectivity"""
    try:
        redis_client.ping()
        return True
    except Exception as e:
        logger.error(f"Redis check failed: {e}")
        return False

# Metrics endpoint
@app.route('/metrics')
def metrics():
    """Prometheus metrics endpoint"""
    return generate_latest()

# API versioning
@app.route('/api/v1/orders', methods=['POST'])
@require_auth
@limiter.limit(f"{Config.RATE_LIMIT_PER_MINUTE} per minute")
@order_histogram.time()
def create_order():
    """
    Create new order with full validation and error handling.
    
    Production features:
    - Input validation
    - Database transactions
    - Caching
    - Event publishing
    - Error recovery
    - Audit logging
    """
    try:
        # Validate input
        data = request.get_json()
        if not data:
            return jsonify({'error': 'Invalid JSON'}), 400
        
        required_fields = ['product_id', 'quantity']
        missing = [f for f in required_fields if f not in data]
        if missing:
            return jsonify({'error': f'Missing fields: {missing}'}), 400
        
        # Validate types and ranges
        if not isinstance(data['quantity'], (int, float)) or data['quantity'] <= 0:
            return jsonify({'error': 'Invalid quantity'}), 400
        
        # Start database transaction
        session = Session()
        try:
            # Check inventory (with cache)
            cache_key = f"inventory:{data['product_id']}"
            inventory = redis_client.get(cache_key)
            
            if inventory is None:
                # Fetch from database
                inventory = check_inventory(data['product_id'])
                # Cache for 5 minutes
                redis_client.setex(cache_key, 300, json.dumps(inventory))
            else:
                inventory = json.loads(inventory)
            
            if inventory['available'] < data['quantity']:
                return jsonify({'error': 'Insufficient inventory'}), 400
            
            # Calculate pricing
            total_amount = calculate_price(
                data['product_id'],
                data['quantity'],
                g.user_id
            )
            
            # Create order
            order = Order(
                id=generate_order_id(),
                user_id=g.user_id,
                product_id=data['product_id'],
                quantity=data['quantity'],
                total_amount=total_amount,
                status='pending'
            )
            
            session.add(order)
            session.commit()
            
            # Clear relevant caches
            redis_client.delete(f"user_orders:{g.user_id}")
            
            # Publish event (for async processing)
            publish_event('order.created', {
                'order_id': order.id,
                'user_id': order.user_id,
                'amount': order.total_amount
            })
            
            # Audit log
            logger.info(f"Order created: {order.id} by user {g.user_id}")
            
            # Update metrics
            order_counter.inc()
            
            return jsonify({
                'id': order.id,
                'status': order.status,
                'total_amount': order.total_amount,
                'created_at': order.created_at.isoformat()
            }), 201
            
        except IntegrityError as e:
            session.rollback()
            error_counter.labels('database_integrity').inc()
            logger.error(f"Database integrity error: {e}")
            return jsonify({'error': 'Order creation failed'}), 400
        except Exception as e:
            session.rollback()
            raise
        finally:
            session.close()
            
    except Exception as e:
        error_counter.labels('unexpected').inc()
        logger.exception(f"Unexpected error in create_order: {e}")
        sentry_sdk.capture_exception(e)
        return jsonify({'error': 'Internal server error'}), 500

@app.route('/api/v1/orders/<order_id>', methods=['GET'])
@require_auth
@limiter.limit(f"{Config.RATE_LIMIT_PER_MINUTE * 2} per minute")
def get_order(order_id):
    """Get order with caching"""
    
    # Check cache first
    cache_key = f"order:{order_id}"
    cached = redis_client.get(cache_key)
    
    if cached:
        logger.debug(f"Cache hit for order {order_id}")
        return jsonify(json.loads(cached))
    
    # Fetch from database
    session = Session()
    try:
        order = session.query(Order).filter_by(
            id=order_id,
            user_id=g.user_id  # Ensure user owns this order
        ).first()
        
        if not order:
            return jsonify({'error': 'Order not found'}), 404
        
        result = {
            'id': order.id,
            'product_id': order.product_id,
            'quantity': order.quantity,
            'total_amount': order.total_amount,
            'status': order.status,
            'created_at': order.created_at.isoformat(),
            'updated_at': order.updated_at.isoformat()
        }
        
        # Cache for 1 minute
        redis_client.setex(cache_key, 60, json.dumps(result))
        
        return jsonify(result)
        
    finally:
        session.close()

# Helper functions
def generate_order_id():
    """Generate unique order ID"""
    import uuid
    return str(uuid.uuid4())

def check_inventory(product_id):
    """Check product inventory (mock implementation)"""
    # In production, query inventory service/database
    return {'product_id': product_id, 'available': 100}

def calculate_price(product_id, quantity, user_id):
    """Calculate order price with discounts"""
    # In production, query pricing service
    base_price = 29.99
    discount = get_user_discount(user_id)
    return round(base_price * quantity * (1 - discount), 2)

def get_user_discount(user_id):
    """Get user discount rate"""
    # In production, check user tier/loyalty program
    return 0.1  # 10% discount

def publish_event(event_type, data):
    """Publish event to message queue"""
    # In production, use RabbitMQ/Kafka/SQS
    logger.info(f"Event published: {event_type} - {data}")

# Error handlers
@app.errorhandler(404)
def not_found(error):
    return jsonify({'error': 'Not found'}), 404

@app.errorhandler(500)
def internal_error(error):
    logger.error(f"Internal error: {error}")
    return jsonify({'error': 'Internal server error'}), 500

if __name__ == '__main__':
    # Create tables
    Base.metadata.create_all(engine)
    
    # Run app (use gunicorn/uwsgi in production)
    app.run(
        host='0.0.0.0',
        port=int(os.environ.get('PORT', 5000)),
        debug=Config.ENVIRONMENT == 'development'
    )
```

### 📊 Data Processing Scenario
**Production data pipeline with monitoring**

```python
"""
Real-World Scenario: ETL Pipeline for Analytics
Production data processing with error recovery and monitoring
"""

import os
import logging
import time
from datetime import datetime, timedelta
from typing import Dict, List, Any, Optional
import pandas as pd
import numpy as np
from dataclasses import dataclass
import asyncio
import aioboto3
from sqlalchemy import create_engine
import pyarrow.parquet as pq
import pyarrow as pa
from prometheus_client import Counter, Histogram, Gauge
import sentry_sdk

# Metrics
records_processed = Counter('etl_records_processed', 'Total records processed')
processing_time = Histogram('etl_processing_seconds', 'Processing time')
pipeline_errors = Counter('etl_errors', 'Pipeline errors', ['stage'])
data_quality_gauge = Gauge('etl_data_quality', 'Data quality score')

@dataclass
class PipelineConfig:
    """Production pipeline configuration"""
    source_bucket: str = os.environ.get('SOURCE_BUCKET')
    dest_bucket: str = os.environ.get('DEST_BUCKET')
    database_url: str = os.environ.get('DATABASE_URL')
    batch_size: int = int(os.environ.get('BATCH_SIZE', '10000'))
    max_retries: int = int(os.environ.get('MAX_RETRIES', '3'))
    quality_threshold: float = float(os.environ.get('QUALITY_THRESHOLD', '0.95'))

class DataPipeline:
    """Production ETL pipeline with monitoring and error recovery"""
    
    def __init__(self, config: PipelineConfig):
        self.config = config
        self.logger = self._setup_logging()
        self.engine = create_engine(config.database_url, pool_size=10)
        self.checkpoint_manager = CheckpointManager()
    
    def _setup_logging(self):
        """Configure structured logging"""
        logger = logging.getLogger(__name__)
        handler = logging.StreamHandler()
        formatter = logging.Formatter(
            '%(asctime)s - %(name)s - %(levelname)s - %(message)s'
        )
        handler.setFormatter(formatter)
        logger.addHandler(handler)
        logger.setLevel(logging.INFO)
        return logger
    
    async def run(self, date: datetime) -> Dict[str, Any]:
        """
        Run complete ETL pipeline with monitoring.
        
        Production features:
        - Checkpointing for recovery
        - Data validation
        - Quality checks
        - Performance monitoring
        - Error handling
        """
        pipeline_id = f"pipeline_{date.strftime('%Y%m%d')}_{int(time.time())}"
        self.logger.info(f"Starting pipeline: {pipeline_id}")
        
        results = {
            'pipeline_id': pipeline_id,
            'start_time': datetime.utcnow(),
            'status': 'running',
            'stages': {}
        }
        
        try:
            # Stage 1: Extract
            with processing_time.labels('extract').time():
                raw_data = await self.extract(date)
                results['stages']['extract'] = {
                    'records': len(raw_data),
                    'status': 'completed'
                }
                self.checkpoint_manager.save(pipeline_id, 'extract', raw_data)
            
            # Stage 2: Transform
            with processing_time.labels('transform').time():
                transformed = await self.transform(raw_data)
                results['stages']['transform'] = {
                    'records': len(transformed),
                    'status': 'completed'
                }
                self.checkpoint_manager.save(pipeline_id, 'transform', transformed)
            
            # Stage 3: Validate
            quality_score = self.validate(transformed)
            results['stages']['validate'] = {
                'quality_score': quality_score,
                'status': 'completed'
            }
            data_quality_gauge.set(quality_score)
            
            if quality_score < self.config.quality_threshold:
                raise DataQualityError(
                    f"Quality score {quality_score} below threshold "
                    f"{self.config.quality_threshold}"
                )
            
            # Stage 4: Load
            with processing_time.labels('load').time():
                await self.load(transformed)
                results['stages']['load'] = {
                    'records': len(transformed),
                    'status': 'completed'
                }
            
            results['status'] = 'completed'
            results['end_time'] = datetime.utcnow()
            
            # Update metrics
            records_processed.inc(len(transformed))
            
            self.logger.info(f"Pipeline completed: {pipeline_id}")
            return results
            
        except Exception as e:
            results['status'] = 'failed'
            results['error'] = str(e)
            pipeline_errors.labels(results.get('current_stage', 'unknown')).inc()
            
            self.logger.error(f"Pipeline failed: {e}")
            sentry_sdk.capture_exception(e)
            
            # Attempt recovery
            if self.checkpoint_manager.can_recover(pipeline_id):
                self.logger.info("Attempting pipeline recovery...")
                return await self.recover_pipeline(pipeline_id)
            
            raise
    
    async def extract(self, date: datetime) -> pd.DataFrame:
        """Extract data from S3 with retry logic"""
        
        async with aioboto3.Session().client('s3') as s3:
            prefix = f"data/{date.strftime('%Y/%m/%d')}/"
            
            # List objects
            paginator = s3.get_paginator('list_objects_v2')
            pages = paginator.paginate(
                Bucket=self.config.source_bucket,
                Prefix=prefix
            )
            
            dataframes = []
            async for page in pages:
                if 'Contents' not in page:
                    continue
                
                for obj in page['Contents']:
                    # Download and read parquet files
                    response = await s3.get_object(
                        Bucket=self.config.source_bucket,
                        Key=obj['Key']
                    )
                    
                    data = await response['Body'].read()
                    df = pd.read_parquet(io.BytesIO(data))
                    dataframes.append(df)
            
            if not dataframes:
                raise ValueError(f"No data found for date {date}")
            
            return pd.concat(dataframes, ignore_index=True)
    
    async def transform(self, data: pd.DataFrame) -> pd.DataFrame:
        """Transform data with business logic"""
        
        # Data cleaning
        data = data.dropna(subset=['user_id', 'timestamp'])
        data['timestamp'] = pd.to_datetime(data['timestamp'])
        
        # Feature engineering
        data['hour'] = data['timestamp'].dt.hour
        data['day_of_week'] = data['timestamp'].dt.dayofweek
        data['is_weekend'] = data['day_of_week'].isin([5, 6])
        
        # Aggregations
        user_stats = data.groupby('user_id').agg({
            'amount': ['sum', 'mean', 'count'],
            'timestamp': ['min', 'max']
        })
        
        # Flatten column names
        user_stats.columns = ['_'.join(col).strip() for col in user_stats.columns]
        user_stats = user_stats.reset_index()
        
        return user_stats
    
    def validate(self, data: pd.DataFrame) -> float:
        """Validate data quality"""
        
        checks = {
            'completeness': (data.notna().sum() / len(data)).mean(),
            'uniqueness': len(data['user_id'].unique()) / len(data),
            'validity': self._check_business_rules(data),
            'consistency': self._check_consistency(data)
        }
        
        # Weighted quality score
        weights = {'completeness': 0.3, 'uniqueness': 0.2, 
                  'validity': 0.3, 'consistency': 0.2}
        
        quality_score = sum(
            checks[metric] * weight 
            for metric, weight in weights.items()
        )
        
        self.logger.info(f"Data quality checks: {checks}")
        return quality_score
    
    def _check_business_rules(self, data: pd.DataFrame) -> float:
        """Check business rule compliance"""
        valid = 0
        total = len(data)
        
        # Example business rules
        valid += (data['amount_sum'] >= 0).sum()
        valid += (data['amount_count'] > 0).sum()
        
        return valid / (total * 2) if total > 0 else 0
    
    def _check_consistency(self, data: pd.DataFrame) -> float:
        """Check data consistency"""
        # Check if calculated fields are consistent
        return 1.0  # Simplified for example
    
    async def load(self, data: pd.DataFrame) -> None:
        """Load data to destination with partitioning"""
        
        # Partition by date for efficient querying
        data['partition_date'] = pd.Timestamp.now().strftime('%Y%m%d')
        
        # Write to S3 as Parquet
        table = pa.Table.from_pandas(data)
        
        async with aioboto3.Session().client('s3') as s3:
            buffer = io.BytesIO()
            pq.write_table(table, buffer, compression='snappy')
            
            key = f"processed/{data['partition_date'].iloc[0]}/data.parquet"
            
            await s3.put_object(
                Bucket=self.config.dest_bucket,
                Key=key,
                Body=buffer.getvalue()
            )
        
        # Also write to database for real-time queries
        data.to_sql(
            'user_analytics',
            self.engine,
            if_exists='append',
            index=False,
            method='multi',
            chunksize=self.config.batch_size
        )
    
    async def recover_pipeline(self, pipeline_id: str) -> Dict[str, Any]:
        """Recover failed pipeline from checkpoint"""
        
        checkpoint = self.checkpoint_manager.get_latest(pipeline_id)
        if not checkpoint:
            raise ValueError(f"No checkpoint found for {pipeline_id}")
        
        self.logger.info(f"Recovering from stage: {checkpoint['stage']}")
        
        # Resume from last successful stage
        if checkpoint['stage'] == 'extract':
            data = checkpoint['data']
            return await self.run_from_transform(data)
        elif checkpoint['stage'] == 'transform':
            data = checkpoint['data']
            return await self.run_from_load(data)
        
        raise ValueError(f"Unknown checkpoint stage: {checkpoint['stage']}")

class CheckpointManager:
    """Manage pipeline checkpoints for recovery"""
    
    def save(self, pipeline_id: str, stage: str, data: Any):
        """Save checkpoint to persistent storage"""
        # In production, save to S3/database
        pass
    
    def get_latest(self, pipeline_id: str) -> Optional[Dict]:
        """Get latest checkpoint for pipeline"""
        # In production, retrieve from S3/database
        pass
    
    def can_recover(self, pipeline_id: str) -> bool:
        """Check if pipeline can be recovered"""
        return self.get_latest(pipeline_id) is not None

# Production entry point
async def main():
    config = PipelineConfig()
    pipeline = DataPipeline(config)
    
    # Run for yesterday's data
    date = datetime.utcnow() - timedelta(days=1)
    
    try:
        results = await pipeline.run(date)
        print(f"Pipeline results: {results}")
    except Exception as e:
        logging.error(f"Pipeline failed: {e}")
        raise

if __name__ == '__main__':
    asyncio.run(main())
```

## Deployment Configurations

### Docker Configuration
```dockerfile
# Production Dockerfile
FROM python:3.11-slim

# Security: Run as non-root user
RUN useradd -m -u 1000 appuser

WORKDIR /app

# Install dependencies
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copy application
COPY --chown=appuser:appuser . .

# Security: Switch to non-root user
USER appuser

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD python -c "import requests; requests.get('http://localhost:8000/health')"

# Run application
CMD ["gunicorn", "--bind", "0.0.0.0:8000", "--workers", "4", "--worker-class", "uvicorn.workers.UvicornWorker", "main:app"]
```

### Kubernetes Deployment
```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: api-deployment
spec:
  replicas: 3
  selector:
    matchLabels:
      app: api
  template:
    metadata:
      labels:
        app: api
    spec:
      containers:
      - name: api
        image: myapp:latest
        ports:
        - containerPort: 8000
        env:
        - name: DATABASE_URL
          valueFrom:
            secretKeyRef:
              name: db-secret
              key: url
        resources:
          requests:
            memory: "256Mi"
            cpu: "250m"
          limits:
            memory: "512Mi"
            cpu: "500m"
        livenessProbe:
          httpGet:
            path: /health
            port: 8000
          initialDelaySeconds: 30
          periodSeconds: 10
        readinessProbe:
          httpGet:
            path: /health/ready
            port: 8000
          initialDelaySeconds: 5
          periodSeconds: 5
```

## Success Criteria
✅ Solves real business problems
✅ Production-ready features included
✅ Scalable architecture
✅ Security best practices
✅ Monitoring and observability
✅ Error recovery mechanisms
✅ Deployment ready