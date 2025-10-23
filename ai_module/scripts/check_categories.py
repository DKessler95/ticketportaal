"""
Check available categories
"""
import mysql.connector

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'ticketportaal'
}

conn = mysql.connector.connect(**DB_CONFIG)
cursor = conn.cursor(dictionary=True)

print("Available categories:")
print("=" * 60)

cursor.execute("SELECT * FROM categories LIMIT 1")
categories = cursor.fetchall()

if categories:
    print("Columns:", list(categories[0].keys()))
    print("\nFetching all categories...")
    cursor.execute("SELECT * FROM categories")
    all_cats = cursor.fetchall()
    for cat in all_cats:
        print(cat)
else:
    print("No categories found!")

cursor.close()
conn.close()
