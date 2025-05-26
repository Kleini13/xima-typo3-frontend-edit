# TYPO3 Frontend Edit Extension - Troubleshooting Guide

## Error: "Failed to fetch content elements"

This error occurs when the JavaScript frontend cannot communicate with the TYPO3 backend middleware. Here are the most common causes and solutions:

## 1. Quick Debugging Steps

### Step 1: Check Browser Console
1. Open your browser's Developer Tools (F12)
2. Go to the Console tab
3. Look for detailed error messages from "Frontend Edit"
4. Check the Network tab for failed requests to `?type=1729341864`

### Step 2: Use the Debug Script
1. Copy the `debug_frontend_edit.php` file to your TYPO3 web root
2. Access it via browser: `https://your-domain.com/debug_frontend_edit.php`
3. Check all the status indicators

## 2. Common Causes and Solutions

### Backend User Authentication Issues
**Problem**: No backend user is logged in or session expired
**Solution**: 
- Ensure you're logged into the TYPO3 backend
- Check if backend session cookies are being sent
- Verify backend user has page access permissions

### Middleware Not Registered
**Problem**: The middleware is not properly registered
**Solution**:
- Clear TYPO3 cache: `vendor/bin/typo3 cache:flush`
- Check if `Configuration/RequestMiddlewares.php` exists
- Verify extension is installed and activated

### CORS/Cookie Issues
**Problem**: Cookies not being sent with AJAX requests
**Solution**:
- Ensure frontend and backend are on the same domain
- Check if `SameSite` cookie settings are blocking requests
- Verify HTTPS/HTTP consistency

### Page Access Permissions
**Problem**: Backend user doesn't have access to the current page
**Solution**:
- Check user/group permissions for the page
- Verify page is not hidden or deleted
- Check if page is in a restricted branch

### Extension Configuration Issues
**Problem**: Extension configuration is missing or invalid
**Solution**:
- Go to Admin Tools > Settings > Extension Configuration
- Configure `xima_typo3_frontend_edit` settings
- Check for required settings like `simpleMode`, `linkTargetBlank`, etc.

## 3. Advanced Debugging

### Enable PHP Error Logging
Add to your `LocalConfiguration.php`:
```php
'LOG' => [
    'TYPO3' => [
        'CMS' => [
            'deprecations' => [
                'writerConfiguration' => [
                    \TYPO3\CMS\Core\Log\LogLevel::NOTICE => [
                        \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
                            'logFile' => 'typo3temp/logs/deprecations.log'
                        ],
                    ],
                ],
            ],
        ],
    ],
],
'SYS' => [
    'displayErrors' => 1,
    'devIPmask' => '*',
    'exceptionalErrors' => E_ALL & ~(E_STRICT | E_NOTICE | E_COMPILE_WARNING | E_COMPILE_ERROR | E_CORE_WARNING | E_CORE_ERROR | E_PARSE | E_ERROR | E_DEPRECATED | E_USER_DEPRECATED),
],
```

### Check TYPO3 Logs
Look in these locations for error messages:
- `typo3temp/logs/typo3.log`
- `var/log/typo3.log` (TYPO3 v9+)
- Web server error logs

### Test the Endpoint Directly
Test the middleware endpoint directly:
```bash
curl -X POST "https://your-domain.com/?type=1729341864" \
  -H "Content-Type: application/json" \
  -H "Cookie: your-backend-session-cookie" \
  -d '{}'
```

## 4. TYPO3 v13 Specific Issues

### Routing Changes
TYPO3 v13 has updated routing mechanisms. Ensure:
- Site configuration is properly set up
- Language configuration is correct
- Base URLs are configured properly

### Backend Authentication Changes
TYPO3 v13 may have stricter backend authentication:
- Check if backend user sessions are properly maintained
- Verify cookie settings in site configuration
- Check for any custom authentication middleware conflicts

## 5. Content Element Issues

### Missing Content Elements
If no content elements are found:
- Check if page has content elements in the correct language
- Verify content elements are not hidden or deleted
- Check if content elements have proper `uid` and `pid` values

### Data Attribute Issues
If data collection fails:
- Ensure content elements have proper `id` attributes (format: `c123`)
- Check if `.frontend-edit--data` elements exist
- Verify JSON data in data elements is valid

## 6. Quick Fixes

### Clear All Caches
```bash
vendor/bin/typo3 cache:flush
vendor/bin/typo3 cache:warmup
```

### Reinstall Extension
```bash
composer remove xima/typo3-frontend-edit
composer require xima/typo3-frontend-edit
vendor/bin/typo3 extension:activate xima_typo3_frontend_edit
```

### Reset Extension Configuration
1. Go to Admin Tools > Settings > Extension Configuration
2. Reset `xima_typo3_frontend_edit` to defaults
3. Clear cache

## 7. Getting Help

If none of these solutions work:

1. **Check the improved error messages** in the browser console (after applying the fixes)
2. **Run the debug script** and share the output
3. **Check TYPO3 and web server logs** for detailed error messages
4. **Verify TYPO3 version compatibility** with the extension
5. **Test on a clean TYPO3 installation** to isolate the issue

## 8. Prevention

To prevent future issues:
- Keep TYPO3 and extensions updated
- Regularly clear caches after updates
- Monitor backend user session timeouts
- Use consistent HTTPS/HTTP protocols
- Maintain proper file permissions
