#! /bin/bash

# Make a string Title Case
title_case() {
  set ${*,,};
  echo ${*^};
}

maxlength() {
  max_length=$1
  string=$2
  prefix=$3
  prefix_len=${#prefix}
  max=$(($max_length - $prefix_len -11))
  # # Initialize a new string to store the split lines
  split_string=""
  # Loop through the original string and split it into multiple lines
  while [ ${#string} -gt $max ]; do
    line="$prefix"
    # Build each line word by word until max length is reached
    while [ ${#line} -lt $max ]; do
      word=$(echo "$string" | cut -f 1 -d " ")
      word_len=${#word}
      line="$line $word"
      string=$(echo $string | cut -c $(($word_len + 2))-)
    done
    split_string+="$line\n"
  done
  # Append the remaining substring to the split string
  split_string+="$prefix $string"
  echo -e "$split_string"
}


echo "New Migration"
read -p 'Describe the purpose of this migration in 5 words or fewer: ' title
# title="Users: add created_by"
read -p 'What will this migration do? ' description
# description="add a created_by column to the users table in the database"
# description="Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer suscipit gravida enim, commodo gravida eros porttitor eget. Vivamus lacinia elit et nulla faucibus, vitae imperdiet sem sagittis. Cras a lorem eu leo placerat ornare. Sed eleifend mattis mauris sed auctor. Mauris ut cursus magna. Vestibulum volutpat facilisis accumsan. Nulla sem lorem, cursus sit amet metus sit amet, finibus tempor quam. Phasellus neque odio, semper sit amet quam aliquam, tincidunt pharetra orci. Nullam sit amet lectus fermentum, pulvinar justo ut, fringilla massa. Morbi quis nulla accumsan, posuere diam id, aliquam diam. Donec volutpat metus id tempor dictum. Pellentesque eget libero enim."



class="${title//[^[:alnum:]]/ }"
echo $class
class=$(title_case $class)
echo $class
class=$(echo $class | sed 's/ //g')
echo $class

date=$(date '+%Y%m%d%H%M')
filename=$(echo "$date-$class.migration")
path="migrations/$filename";

echo "<?php" >> $path
echo "/**" >> $path
echo " * Migration: $title" >> $path
echo " *" >> $path
maxlength 80 "$description" " *" >> $path
echo " *" >> $path
php=$(php -v | perl -lne 'print $1 while /PHP ([0-9\.]{,3})/g')
echo " * PHP version $php" >> $path
echo " *" >> $path
# Get author info
if [ ! -f .author ]; then
  read -p 'Author name: ' name
  read -p 'Author Email: ' email
  echo "$name<$email>" > .author
fi
author=$(cat .author)

echo " * @category   Migration" >> $path
echo " * @package    KnowledgeDirect" >> $path
echo " * @author     $author" >> $path
echo " * @version    1.0" >> $path
since=$(date '+%Y-%m-%d')
echo " * @since      $since" >> $path
echo " */" >> $path
echo "" >> $path
echo "namespace Core\Migrations;" >> $path
echo "" >> $path
echo "require_once CORE_ROOT . '/models/migration.model';" >> $path
echo "" >> $path
echo "" >> $path

echo "class $class extends \Core\Models\Migration" >> $path
echo "{" >> $path
echo "" >> $path
echo "  /**" >> $path
echo "   * Human friendly name for this migration." >> $path
echo "   * @var string" >> $path
echo "   */" >> $path
echo "  public string \$name = '$title';" >> $path
echo "" >> $path
echo "  /**" >> $path
echo "   * Check if this migration has been completed." >> $path
echo "   *" >> $path
echo "   * @return bool True if this migration is complete " >> $path
echo "   */" >> $path
echo "   protected function isComplete(): bool" >> $path
echo "   {" >> $path
echo "     return true;" >> $path
echo "   }" >> $path
echo "   /**" >> $path
maxlength 80 "$description" "    *" >> $path
echo "    *" >> $path
echo "    * @return Boolean TRUE if successful." >> $path
echo "    */" >> $path
echo "   protected function execute(): bool " >> $path
echo "   {" >> $path
echo "     return true;" >> $path
echo "   }" >> $path
echo "}" >> $path
echo ""
echo "$path has been created."
echo "Update this file and commit the result."
echo ""
echo "Run migrations with \`DDEV tetra maintenance\`"
