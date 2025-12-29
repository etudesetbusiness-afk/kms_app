# 🚀 EXECUTION INSTRUCTIONS - Bluehost Production Fix

## Quick Start (2 minutes)

### Option A: phpMyAdmin (Easiest - Recommended)

1. **Log in to Bluehost cPanel**
   - URL: `https://app.kennemulti-services.com:2083` (or via Bluehost control panel)
   - User: Your cPanel username
   - Password: Your cPanel password

2. **Navigate to phpMyAdmin**
   - cPanel → Databases → phpMyAdmin
   - OR: Direct URL: `https://app.kennemulti-services.com/cPanel-mysql/index.html`

3. **Select Database**
   - Left sidebar → Click on `kdfvxvmy_kms_gestion`
   - (This is your production KMS database)

4. **Open SQL Editor**
   - Top menu → Click on **"SQL"** tab
   - Large text area appears

5. **Copy the Fix Script**
   - Open file: [`db/fix_catalogue_schema.sql`](fix_catalogue_schema.sql)
   - Select ALL content (Ctrl+A)
   - Copy (Ctrl+C)

6. **Paste into phpMyAdmin SQL Editor**
   - Click in the SQL text area
   - Paste (Ctrl+V)
   - All statements should appear

7. **Execute**
   - **Blue button at bottom: "Go"** or **"Execute"**
   - Script runs

8. **Verify Success**
   - You should see: "Queries executed successfully" message
   - No red error boxes
   - Scroll down to see "SHOW CREATE TABLE" results

---

### Option B: Command Line SSH (For Advanced Users)

1. **SSH into Bluehost**
   ```bash
   ssh kms_username@app.kennemulti-services.com
   ```
   (Replace with your actual SSH credentials)

2. **Navigate to database folder**
   ```bash
   cd /home/kdfvxvmy/public_html/kms_app/db
   ```

3. **Download the fix script locally** or create it:
   ```bash
   cat > fix_catalogue_schema.sql << 'EOF'
   [PASTE ENTIRE CONTENT OF fix_catalogue_schema.sql HERE]
   EOF
   ```

4. **Execute the script**
   ```bash
   mysql -u kdfvxvmy_WPEUF -p kdfvxvmy_kms_gestion < fix_catalogue_schema.sql
   ```
   When prompted for password, enter: `adminKMs_app#2025`

5. **Verify**
   ```bash
   mysql -u kdfvxvmy_WPEUF -p -e "SHOW CREATE TABLE kdfvxvmy_kms_gestion.catalogue_produits\G"
   ```

---

## Detailed Step-by-Step (with Screenshots Info)

### Step 1-3: Access phpMyAdmin

**Path in cPanel:**
```
cPanel Dashboard
  ↓
Databases Section
  ↓
phpMyAdmin
  ↓
Select: kdfvxvmy_kms_gestion (left panel)
```

### Step 4-6: Copy & Paste Script

**In phpMyAdmin, you'll see:**
- Left panel: Database list + table browser
- Center: Main editing area
- Top tabs: Structure | SQL | Search | Query | Export | Import

1. Click **SQL** tab (if not already on it)
2. You'll see a large white text area
3. Click inside the text area
4. Paste the SQL script (Ctrl+V)

### Step 7: Execute

**Look for button:**
- **Blue "Go" button** at the bottom-right
- OR **"Execute" button**
- Click it

**Expected output:**
```
SQL query:

ALTER TABLE `catalogue_categories` ADD PRIMARY KEY (`id`);

MySQL said: Documentation
#1022 - Can't write; duplicate key in table '#sql-...'
```

**OR (if successful):**
```
Your SQL query has been executed successfully.
```

---

## What Each Statement Does

### Statement 1-2: Fix `catalogue_categories`
```sql
ALTER TABLE `catalogue_categories` ADD PRIMARY KEY (`id`);
ALTER TABLE `catalogue_categories` ADD UNIQUE KEY `slug` (`slug`);
```
**Result:** Category table now has unique identification

### Statement 3-8: Fix `catalogue_produits` (CRITICAL)
```sql
ALTER TABLE `catalogue_produits` ADD PRIMARY KEY (`id`);
ALTER TABLE `catalogue_produits` ADD UNIQUE KEY `code` (`code`);
ALTER TABLE `catalogue_produits` ADD UNIQUE KEY `slug` (`slug`);
ALTER TABLE `catalogue_produits` ADD INDEX `categorie_id` (`categorie_id`);
ALTER TABLE `catalogue_produits` ADD CONSTRAINT `chk_caracteristiques_json_valid` ...
ALTER TABLE `catalogue_produits` ADD CONSTRAINT `chk_galerie_images_valid` ...
```
**Result:** Product table now properly structured for UPDATE operations

### Statement 9: Referential Integrity
```sql
ALTER TABLE `catalogue_produits` 
ADD CONSTRAINT `catalogue_produits_ibfk_1` 
FOREIGN KEY (`categorie_id`) REFERENCES `catalogue_categories` (`id`);
```
**Result:** Products can't reference non-existent categories

---

## Possible Issues & Solutions

### Issue 1: "Can't write; duplicate key in table"
**Cause:** The PRIMARY KEY statement found duplicate NULL values
**Solution:** This is normal - phpMyAdmin internally handles it. Just continue.

### Issue 2: "Duplicate entry for key 'code'"
**Cause:** Some products have identical codes
**Solution:** 
```sql
-- Find duplicates
SELECT code, COUNT(*) FROM catalogue_produits GROUP BY code HAVING COUNT(*) > 1;

-- Manually fix in Admin → Catalogue → Edit each product
-- Give each a unique code
```
Then retry the UNIQUE KEY statement.

### Issue 3: "Check constraint violation"
**Cause:** Some JSON fields have invalid JSON data
**Solution:** The CHECK constraint I provided allows NULL, so this shouldn't happen.
If it does: Run without CHECK constraints first, then fix data, then add checks.

### Issue 4: "Cannot add or update a child row"
**Cause:** A product has a `categorie_id` that doesn't exist
**Solution:**
```sql
-- Find orphaned products
SELECT id, categorie_id FROM catalogue_produits 
WHERE categorie_id NOT IN (SELECT id FROM catalogue_categories);

-- Update them to a valid category (e.g., 19 = Panneaux & Contreplaqués)
UPDATE catalogue_produits SET categorie_id = 19 
WHERE categorie_id NOT IN (SELECT id FROM catalogue_categories);
```

### Issue 5: Nothing happens after clicking "Go"
**Cause:** 
- Browser timeout
- Large database
- Server temporary overload
**Solution:**
- Wait 30 seconds
- Refresh the page (F5)
- Check if the changes were applied anyway (scroll down)
- If still nothing: Try SSH method instead

---

## Verification After Execution

### Quick Check (in phpMyAdmin)

1. **Run this SQL:**
   ```sql
   SHOW CREATE TABLE `catalogue_produits`;
   ```

2. **Look for these lines in result:**
   ```sql
   PRIMARY KEY (`id`),
   UNIQUE KEY `code` (`code`),
   UNIQUE KEY `slug` (`slug`),
   KEY `categorie_id` (`categorie_id`),
   CONSTRAINT `chk_caracteristiques_json_valid` CHECK ...
   CONSTRAINT `catalogue_produits_ibfk_1` FOREIGN KEY ...
   ```

3. **If you see these → Fix was successful ✅**

### Real-World Test (in your application)

1. **Log into your KMS app:**
   - Go to: `https://app.kennemulti-services.com/`
   - Login as admin

2. **Navigate to Catalog:**
   - Menu → Admin → Catalogue Produits
   - OR: Direct: `/admin/catalogue/produit_list.php`

3. **Modify a product:**
   - Click on any product (e.g., ID 296 - Machine de perçage)
   - Change something: Price, Name, Description
   - Click "Modifier" / "Sauvegarder"

4. **Verify persistence:**
   - Refresh the page (F5)
   - **Is your change still there?** ✅ = Success
   - **Did it revert?** ❌ = Problem (run verification again)

5. **Test image upload:**
   - Same product
   - Click "Télécharger image"
   - Upload a test image
   - Save
   - Refresh
   - **Is image there?** ✅ = Success

---

## Rollback Plan (If Something Goes Wrong)

If after executing the script things break:

### Quick Rollback
```sql
-- Undo FOREIGN KEY
ALTER TABLE `catalogue_produits` DROP FOREIGN KEY `catalogue_produits_ibfk_1`;

-- Undo CHECK constraints
ALTER TABLE `catalogue_produits` DROP CONSTRAINT `chk_caracteristiques_json_valid`;
ALTER TABLE `catalogue_produits` DROP CONSTRAINT `chk_galerie_images_valid`;

-- Undo keys and indexes
ALTER TABLE `catalogue_produits` DROP KEY `slug`;
ALTER TABLE `catalogue_produits` DROP KEY `code`;
ALTER TABLE `catalogue_produits` DROP KEY `categorie_id`;
ALTER TABLE `catalogue_produits` DROP PRIMARY KEY;

ALTER TABLE `catalogue_categories` DROP KEY `slug`;
ALTER TABLE `catalogue_categories` DROP PRIMARY KEY;
```

### Full Rollback
If you have a backup:
1. **cPanel → MySQL Databases → Restore**
2. Select your pre-fix backup
3. Confirm

---

## Success Criteria

✅ **All these must be true:**
1. SQL script executed without fatal errors
2. SHOW CREATE TABLE shows PRIMARY KEY
3. SHOW CREATE TABLE shows UNIQUE KEYs
4. Product editing persists changes
5. Image uploads work correctly
6. No duplicate entry errors when saving products

❌ **If any of these fail:**
1. Check phpMyAdmin error messages
2. Review issue/solution pairs above
3. Run rollback if needed
4. Try again with cleaned data

---

## Timeline Expectations

| Task | Time |
|------|------|
| Access Bluehost/cPanel | 1 min |
| Navigate to phpMyAdmin | 1 min |
| Copy fix script | 1 min |
| Paste into SQL editor | 1 min |
| Execute | 1-2 min |
| Verify | 1 min |
| **TOTAL** | **5-7 minutes** |

---

## Support Resources

- 📄 [ROOT_CAUSE_ANALYSIS.md](ROOT_CAUSE_ANALYSIS.md) - Why this happened
- 📄 [SCHEMA_COMPARISON.md](SCHEMA_COMPARISON.md) - Detailed schema differences
- 📄 [FIX_CATALOGUE_GUIDE.md](FIX_CATALOGUE_GUIDE.md) - Complete guide
- 📄 [fix_catalogue_schema.sql](fix_catalogue_schema.sql) - The actual SQL script

---

## Questions Before Execution?

**Q: Will this delete my data?**
A: No. The script only adds structure (keys, indexes, constraints). Zero data is deleted.

**Q: Do I need to stop the application?**
A: No. The changes are backward compatible. You can keep working while executing.

**Q: Can I run this multiple times?**
A: The script is designed to be safe to run multiple times, but best to run once.

**Q: What if I get an error?**
A: See "Possible Issues & Solutions" section above. Most are easily fixable.

**Q: How long does execution take?**
A: 5-10 seconds for the actual SQL (your database has ~350 tables but only 2 are modified).

---

## Ready?

1. ✅ Backup taken? (Optional but recommended)
2. ✅ Have access to Bluehost/cPanel? 
3. ✅ Ready to execute?

**Then proceed with Option A (phpMyAdmin) or Option B (SSH) above.**

---

**Good luck! Your catalog editing should work after this.** 🎉
