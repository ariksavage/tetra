#! /bin/bash
echo "Installing TETRA"

# echo "Testing database connection..."
# sleep 1;
is_ddev=$(printenv "IS_DDEV_PROJECT");
if [ $is_ddev ]; then

db_user=$(printenv "PGUSER");
db_host=$(printenv "PGHOST");
db_pass=$(printenv "PGPASSWORD");
db_name=$(printenv "PGDATABASE");
else
  echo "get config from file"
fi
if mysql -u "$db_user" -p"$db_pass" -h "$db_host" "$db_name" -e ""; then
  echo -e '\e[1A\e[KDatabase connection succeeded'
else
  echo "No database connection found."
  exit 1
fi

base_sql="tetra/base.sql"
echo "Importing $base_sql..."
if mysql -u "$db_user" -p"$db_pass" -h "$db_host" "$db_name" < "$base_sql"; then
  echo -e '\e[1A\e[KImported base.sql.'
else
  echo "Unable to import base.sql"
  exit 2
fi
echo "Configuring user..."
read -p 'Username: ' username
read -sp 'Password: ' password
echo ""
# echo Your username is $username, we will not display your password
read -p 'Email: ' email
read -p 'First Name: ' firstname
read -p 'Last name: ' lastname
json="{\"username\": \"$username\", \"password\": \"$password\", }"
hash=$(php -r "echo password_hash('$password', PASSWORD_BCRYPT, ['cost' => 12]);")
user_q="INSERT INTO users (username, password, email, first_name, last_name) VALUES ( '$username', '$hash', '$email', '$firstname', '$lastname')"
if mysql -u "$db_user" -p"$db_pass" -h "$db_host" "$db_name" -e "$user_q"; then
  # add roles, permissions
  role_q="INSERT INTO user_role_assignments (user_id, role_id) VALUES ((SELECT id FROM users WHERE username='$username'),( SELECT id FROM roles WHERE title='Root'))"
  if mysql -u "$db_user" -p"$db_pass" -h "$db_host" "$db_name" -e "$role_q"; then
    echo Your username is $username, we will not display your password
  fi
fi
