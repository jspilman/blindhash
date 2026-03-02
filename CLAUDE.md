# blindhash

Magento 1.x extension implementing the BlindHash secure password hashing system. Fork of `IshanAtlas/blindhash`. This is a **reference client integration** — it shows how an application integrates with the TapLink API for blind password hashing.

## What This Is

A complete Magento module (`BlindHash_SecurePassword`) that:
- Replaces Magento's built-in password hashing with TapLink blind hashing
- Automatically upgrades existing password hashes on user login
- Provides admin UI for bulk upgrade/downgrade of stored hashes
- Includes a standalone PHP client library for the TapLink API

## TapLink Client Library (`lib/Client.php`)

The most reusable component — a PHP client for the BlindHash API:

```php
$client = new Client($appId);
$client->newPassword($hash1Hex);           // Create new blind hash
$client->verifyPassword($hash1, $hash2, $vid); // Verify password
$client->getSalt($hash1Hex, $versionId);   // Get salt from server
```

- HMAC-SHA512 for all hashing
- Multi-server failover with retry logic
- CURL-based HTTPS communication
- JSON request/response format

## Password Flow

**Registration:**
1. Generate random `salt1` (64 bytes)
2. `hash1 = HMAC-SHA512(salt1, password)`
3. Call `getSalt(hash1)` → receive `salt2`, `versionId`
4. `hash2 = HMAC-SHA512(salt2, password)`
5. Store: `T$hash2$salt1$versionId$magentoHash1`

**Login verification:**
1. Extract `salt1` and `versionId` from stored hash
2. `hash1 = HMAC-SHA512(salt1, password)`
3. Call `verifyPassword(hash1, storedHash2, versionId)`
4. Server returns match/no-match (+ optional upgrade info)

## Hash Storage Format

```
T$[encrypted_hash2]$[encrypted_salt]$[version]$[magento_hash1]
```

Three hash versions supported:
- Version 1: Legacy Magento (no salt)
- Version 2: Legacy Magento (with salt)
- Version 3: BlindHash (default)

## Magento Integration

**Event observers** (automatic migration on login):
- `customer_customer_authenticated`
- `admin_user_authenticate_after`
- `api_user_authenticated`
- `admin_system_config_changed_section_blindhash`

**Admin UI** (System > Configuration > Blind Hash):
- Enable/disable toggle
- AppID configuration
- Public key display
- Upgrade/Downgrade buttons
- Hash count statistics

**Database changes:**
- `admin_user.password` → TEXT (from VARCHAR)
- `api_user.api_key` → TEXT (from VARCHAR)
- Customer `password_hash` attribute → TEXT backend

## Key Files

| File | Purpose |
|------|---------|
| lib/Client.php | TapLink API client (reusable) |
| lib/Response.php | API response wrapper with property mapping |
| Model/Encryption.php | Magento encryption model override |
| Model/Observer.php | Event-driven automatic hash migration |
| Model/Upgrade.php | Bulk upgrade all customer passwords |
| Model/Downgrade.php | Bulk downgrade (revert to Magento hashing) |
| Model/Hashes.php | Hash count queries |
| etc/config.xml | Module registration and event bindings |
| etc/system.xml | Admin UI configuration fields |
| Sample Script.zip | Standalone usage example |

## Development History

- 66 commits, Sep 2017 – Feb 2018
- Developed by AtlasSoftWeb team (Rajesh Puraswani, Kana)
- Fork maintained by jspilman
