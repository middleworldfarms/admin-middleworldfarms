# Vector Database Password Recovery Guide

## 🔐 Current Strong Password Information

**Database**: vector_db  
**Username**: vector_user  
**Password**: `v2WyfrCHBF0CruQ+PpAiQ+Y6w4Q4hIsExcYzfU4aAIo=`  
**Generated**: August 19, 2025  

## 📍 Password Storage Locations

### Primary Location
```
/opt/sites/admin.middleworldfarms.org/ai_service/.env
```

### Backup Locations
```
/opt/sites/admin.middleworldfarms.org/shared_rag_service.py (fallback default)
/opt/sites/admin.middleworldfarms.org/ai_service/.env.backup-2025-08-19
/opt/sites/admin.middleworldfarms.org/.env.backup-2025-08-19-with-strong-password
This file (/opt/sites/admin.middleworldfarms.org/VECTOR_DB_PASSWORD_RECOVERY.md)
```

## 🔍 How to Retrieve Password

### Method 1: From .env file
```bash
grep PGVECTOR_PASSWORD /opt/sites/admin.middleworldfarms.org/ai_service/.env
```

### Method 2: From this recovery file
```bash
grep "Password:" /opt/sites/admin.middleworldfarms.org/VECTOR_DB_PASSWORD_RECOVERY.md
```

### Method 3: Test if password works
```bash
PGPASSWORD='v2WyfrCHBF0CruQ+PpAiQ+Y6w4Q4hIsExcYzfU4aAIo=' psql -h localhost -U vector_user -d vector_db -c "SELECT 'Password works!' as status;"
```

## 🔄 If Password is Lost or Corrupted

### Emergency Reset Steps
```bash
# 1. Generate new strong password
NEW_PASS=$(openssl rand -base64 32)
echo "New password: $NEW_PASS"

# 2. Update PostgreSQL
sudo -u postgres psql -c "ALTER USER vector_user PASSWORD '$NEW_PASS';"

# 3. Update .env file
sed -i "s/PGVECTOR_PASSWORD=.*/PGVECTOR_PASSWORD=$NEW_PASS/" /opt/sites/admin.middleworldfarms.org/ai_service/.env

# 4. Update shared RAG service fallback
sed -i "s/password=os.getenv('PGVECTOR_PASSWORD', '.*'),/password=os.getenv('PGVECTOR_PASSWORD', '$NEW_PASS'),/" /opt/sites/admin.middleworldfarms.org/shared_rag_service.py

# 5. Update this recovery file
sed -i "s/Password\`: \`.*\`/Password\`: \`$NEW_PASS\`/" /opt/sites/admin.middleworldfarms.org/VECTOR_DB_PASSWORD_RECOVERY.md

# 6. Test new password
PGPASSWORD="$NEW_PASS" psql -h localhost -U vector_user -d vector_db -c "SELECT 'New password works!' as status;"
```

## 🛡️ Security Features

- **32-character base64 encoded password** (high entropy)
- **Local database access only** (localhost)
- **Limited user permissions** (no superuser, no create roles/db)
- **Read-only to sensitive data** (only AI knowledge vectors)

## 📋 Password Validation

To verify password is working:
```bash
# Test connection
PGPASSWORD='v2WyfrCHBF0CruQ+PpAiQ+Y6w4Q4hIsExcYzfU4aAIo=' psql -h localhost -U vector_user -d vector_db -c "\conninfo"

# Test RAG service
python3 -c "
import os
os.environ['PGVECTOR_PASSWORD'] = 'v2WyfrCHBF0CruQ+PpAiQ+Y6w4Q4hIsExcYzfU4aAIo='
from shared_rag_service import SharedRAGService
rag = SharedRAGService()
print('RAG Connection:', rag.test_connection())
"
```

---
**Last Updated**: August 19, 2025  
**Security Level**: High (32-char password, restricted user)  
**Access Level**: Local only, AI knowledge database only
