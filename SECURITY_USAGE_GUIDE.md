# 📖 Руководство по использованию системы безопасности

**Версия:** 2.0  
**Статус:** ✅ **АКТУАЛЬНАЯ ДОКУМЕНТАЦИЯ**

---

## 🚀 Быстрый старт

### **Рекомендуемый способ (через security_init.php):**

1. **Добавьте в начало PHP файла:**
```php
<?php
// Подключаем систему безопасности
require_once __DIR__ . '/security_init.php';

// Для обычной страницы
initPageSecurity('your_page.php');

// Для админской страницы
initPageSecurity('admin_page.php', true);
```

2. **Добавьте перед закрывающим `</body>`:**
```php
<?php echo generateSecurityScript(); ?>
```

### **Альтернативный способ (через классы):**

1. **Добавьте в начало PHP файла:**
```php
<?php
require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/src/Security/PageSecurityHelper.php';
use App\Security\PageSecurityHelper;

// Для обычной страницы
PageSecurityHelper::initializePageSecurity('your_page.php');

// Для админской страницы
PageSecurityHelper::initializePageSecurity('admin_page.php', true);
```

2. **Добавьте перед закрывающим `</body>`:**
```php
<?php echo PageSecurityHelper::generateClientSideSecurityScript(); ?>
```

## 🛡️ Уровни защиты

### **Базовая защита (для всех пользователей):**
```php
PageSecurityHelper::initializePageSecurity('page.php');
```

### **Админская защита (только для администраторов):**
```php
PageSecurityHelper::initializePageSecurity('admin_page.php', true);
```

## ⚙️ Настройки

### **Изменение интервала проверки сессии:**
```php
// Проверка каждые 2 минуты (120000 мс)
echo PageSecurityHelper::generateClientSideSecurityScript('index.php', 120000);
```

### **Изменение страницы перенаправления:**
```php
// Перенаправление на login.php вместо index.php
echo PageSecurityHelper::generateClientSideSecurityScript('login.php');
```

## 📋 Что происходит автоматически

1. **Проверка авторизации** - пользователь должен быть авторизован
2. **Установка безопасных заголовков** - защита от XSS, clickjacking и т.д.
3. **Логирование доступа** - все обращения к странице записываются в лог
4. **Клиентская проверка** - дополнительная защита через JavaScript
5. **Периодическая валидация сессии** - проверка каждые 5 минут

## 🔍 Мониторинг

### **Просмотр логов безопасности:**
```bash
# Все события доступа к страницам
grep "page_accessed" logs/security.log

# Проблемы с сессиями
grep "session_corrupted\|forced_logout" logs/security.log

# Rate limiting
grep "rate_limit" logs/security.log
```

## ❗ Важные моменты

- **Всегда используйте** `PageSecurityHelper` для новых страниц
- **Не забывайте** добавлять клиентскую защиту
- **Регулярно проверяйте** логи безопасности
- **Тестируйте** защиту после внесения изменений

## 🆘 Устранение проблем

### **Страница не перенаправляет:**
- Проверьте, что `PageSecurityHelper::initializePageSecurity()` вызывается в начале файла
- Убедитесь, что нет вывода до вызова функции

### **JavaScript ошибки:**
- Проверьте, что `generateClientSideSecurityScript()` добавлен перед `</body>`
- Убедитесь, что функция `showNotification` доступна (для страниц с модальными окнами)

### **Проблемы с сессией:**
- Проверьте настройки `SESSION_TIMEOUT` в config.php
- Убедитесь, что метод `checkSession` работает в AuthController