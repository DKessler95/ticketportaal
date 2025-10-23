"""
Check database schema for tickets and comments
"""
import mysql.connector

DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'ticketportaal'
}

conn = mysql.connector.connect(**DB_CONFIG)
cursor = conn.cursor()

print("TICKETS TABLE STRUCTURE:")
print("=" * 60)
cursor.execute("DESCRIBE tickets")
for row in cursor.fetchall():
    print(f"{row[0]:<30} {row[1]:<20} {row[2]}")

print("\n\nTICKET_COMMENTS TABLE STRUCTURE:")
print("=" * 60)
cursor.execute("DESCRIBE ticket_comments")
for row in cursor.fetchall():
    print(f"{row[0]:<30} {row[1]:<20} {row[2]}")

print("\n\nEXISTING TICKETS:")
print("=" * 60)
cursor.execute("SELECT ticket_id, ticket_number, title FROM tickets LIMIT 5")
for row in cursor.fetchall():
    print(row)

cursor.close()
conn.close()
