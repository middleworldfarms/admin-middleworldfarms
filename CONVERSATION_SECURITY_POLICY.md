# CONVERSATION DATA SECURITY POLICY

## Data Storage Location
- **Primary Storage**: MySQL database (`admin_db.conversations` table)
- **Server Location**: `/opt/sites/admin.middleworldfarms.org/` (Laravel application)
- **Backup Storage**: Included in Laravel backup system to secure backup location

## Security Measures Implemented

### 1. Git Security
- Added comprehensive `.gitignore` rules to prevent conversation data commits
- Excluded all conversation exports, logs, and database dumps
- OAuth2 keys and sensitive files are git-ignored
- Database credentials secured in `.env` file (already git-ignored)

### 2. Database Security
- Conversation data stored in encrypted MySQL database
- Access controlled through Laravel's database abstraction layer
- No direct file storage of conversation content
- User IDs are nullable to support anonymous conversations

### 3. File System Security
- Conversation exports (if any) stored outside web root
- Log files containing conversation data are git-ignored
- Backup files excluded from version control

### 4. Access Control
- API endpoints can be secured with authentication middleware
- Database access restricted to Laravel application
- No direct database exposure

## Current Vulnerabilities to Address

### IMMEDIATE ACTIONS NEEDED:

1. **Environment File Security**
   - `.env` file contains plaintext passwords
   - Should use encrypted credential storage
   - Database password needs rotation

2. **OAuth2 Keys**
   - `oauth2_private.key` and `oauth2_public.key` in web root
   - Should be moved to secure location outside web directory

3. **API Authentication**
   - Conversation API endpoints currently have no authentication
   - Need to add auth middleware for production use

4. **Database Encryption**
   - Consider encrypting sensitive conversation content at database level
   - Add encryption for metadata fields

## Recommended Security Enhancements

### High Priority:
1. Move OAuth2 keys outside web root
2. Add API authentication to conversation endpoints
3. Implement database field encryption for messages
4. Set up proper log rotation and cleanup

### Medium Priority:
1. Add rate limiting to conversation APIs
2. Implement data retention policies
3. Add audit logging for data access
4. Set up database connection encryption

### Low Priority:
1. Add conversation data anonymization tools
2. Implement GDPR compliance features
3. Add conversation export encryption
4. Set up automated security scanning

## Current Backup Status
✅ Conversation data IS included in Laravel backup system
✅ Database backups are created regularly
✅ Backup files are excluded from git
⚠️  Need to verify backup encryption and secure storage location
