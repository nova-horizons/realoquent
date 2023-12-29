# Commands
## realoquent:diff

### Example Output

```
Renamed Column: 
  users.email_verified_timestamp: 
    name: 'email_verified_at' => 'email_verified_timestamp'

New Column: 
  users.team_name

 Review the changes above. Proceed? (yes/no) [yes]:
 > y

 Generate migrations? (yes/no) [yes]:
 > y

Schema::table('users', function(\Illuminate\Database\Schema\Blueprint $table) {
    $table->renameColumn('email_verified_at', 'email_verified_timestamp');
});

Schema::table('users', function (\Illuminate\Database\Schema\Blueprint $table) {
     $table->string('team_name')->nullable();
});

 Review the above migration. Proceed? (You will have a chance to edit before running) (yes/no) [yes]:
 > y

 Enter migration name (your text will be slugified) [schema_migration]:
 > user column changes

Migration file created: database/migrations/2023_12_29_062721_user_column_changes.php

 Review the above migration. Run migrations? (yes/no) [yes]:
 > y

   INFO  Running migrations.  

  2023_12_29_062721_user_column_changes .................................................................................................. 72ms DONE

Models to generate:
    App\Models\User

 Update/generate these models? (yes/no) [yes]:
 > y

Running code style fixer on new files
 2/2 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%

Diff complete!
```
