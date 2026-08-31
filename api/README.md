# RESIDA PRO API v1

Base URL: `https://siten.com/api/`

## Auth
`Authorization: Bearer <token>`

## Endpoints
- `POST /api/login.php` `{username,password}` -> `{token, user}`
- `GET /api/me.php` -> profil
- `GET /api/dues.php?filter=all` -> aidatlar (penalty dahil)
- `GET /api/announcements.php` -> duyurular
- `GET /api/events.php` -> etkinlikler
- `POST /api/pay.php` `{due_id, note, receipt_base64}` -> ödeme bildirimi
- `POST /api/logout.php` -> token iptal

## Örnek
```
curl -X POST https://siten.com/api/login.php -H "Content-Type: application/json" -d '{"username":"ali","password":"123"}'
curl https://siten.com/api/dues.php -H "Authorization: Bearer TOK"
```
Token 30 gün geçerli. Rate limit: 10 hatalı login / 15dk.
