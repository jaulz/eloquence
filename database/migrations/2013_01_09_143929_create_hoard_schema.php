<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Jaulz\Hoard\HoardSchema;

return new class extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
    DB::statement('CREATE SCHEMA IF NOT EXISTS hoard;');

      // Create required functions
    array_map(function (string $statement) {
      DB::statement($statement);
    }, [
      ...HoardSchema::createGenericHelperFunctions(),
      ...HoardSchema::createSpecificHelperFunctions(),
      ...HoardSchema::createAggregationFunctions(),
      ...HoardSchema::createUpdateFunctions(),
    ]);
    
    Schema::create(HoardSchema::$cacheSchema . '.triggers', function (Blueprint $table) {
      $table->id()->generatedAs();

      $table->text('query')->storedAs("CASE WHEN (aggregation_function = '') IS NOT FALSE THEN NULL ELSE 'SELECT ' || foreign_primary_key_name  || ', (' || replace(" . HoardSchema::$cacheSchema . ".get_refresh_query(primary_key_name, aggregation_function, value_names, options, schema_name, table_name, key_name, 'DUMMY', conditions), '''DUMMY''', 'wrapper.' || foreign_primary_key_name) || ') AS ' || cache_aggregation_name || ' FROM ' || foreign_table_name || ' AS wrapper;' END")->nullable();

      $table->text('cache_table_name');
      $table->text('cache_aggregation_name');

      $table->text('schema_name')->nullable();
      $table->text('table_name')->nullable();

      $table->text('aggregation_function')->nullable();
      $table->jsonb('value_names');
      $table->text('conditions');
      $table->text('aggregation_type')->nullable();
      $table->jsonb('options')->default('[]');

      $table->text('key_name')->nullable();
      
      $table->text('foreign_schema_name');
      $table->text('foreign_table_name');
      $table->text('foreign_primary_key_name');

      $table->boolean('manual')->default(false);
      $table->boolean('lazy')->default(false);
      $table->boolean('hidden')->default(false);
      $table->boolean('asynchronous')->default(false);

      $table->text('cache_group_name');
      $table->text('cache_primary_key_name');

      $table->text('primary_key_name')->nullable();

      $table->text('foreign_key_name');
      $table->text('foreign_conditions');
    });
    
    Schema::create(HoardSchema::$cacheSchema . '.logs', function (Blueprint $table) {
      $table->id()->generatedAs();

      $table->unsignedBigInteger('trigger_id');
      $table->foreign('trigger_id')
        ->references('id')->on(HoardSchema::$cacheSchema . '.triggers')
        ->cascadeOnDelete();

      $table->text('operation');
      $table->jsonb('old_values')->nullable();
      $table->text('old_foreign_key')->nullable();
      $table->boolean('old_relevant');
      $table->jsonb('new_values')->nullable();
      $table->text('new_foreign_key')->nullable();
      $table->boolean('new_relevant');

      $table->timestampTz('created_at')->default(DB::raw('NOW()'));
      $table->timestampTz('processed_at')->nullable();
      $table->timestampTz('canceled_at')->nullable();

      $table->index(['trigger_id', 'processed_at']);
    });

    array_map(function (string $statement) {
      DB::statement($statement);
    }, [
      ...HoardSchema::createViewFunctions(),
      ...HoardSchema::createTriggerFunctions(),
      ...HoardSchema::createProcessFunctions(),
      ...HoardSchema::createRefreshFunctions(),

      // Make logs table more performant
      sprintf('ALTER TABLE %1$s.logs' . ' SET UNLOGGED;', HoardSchema::$cacheSchema),

      // Create triggers on "triggers" table
      HoardSchema::execute(sprintf(
        <<<PLPGSQL
          BEGIN
            IF NOT %1\$s.exists_trigger('%1\$s', 'triggers', 'hoard_before') THEN
              CREATE TRIGGER hoard_before
                BEFORE INSERT OR UPDATE OR DELETE ON %1\$s.triggers
                FOR EACH ROW 
                EXECUTE FUNCTION %1\$s.prepare();
            END IF;

            IF NOT %1\$s.exists_trigger('%1\$s', 'triggers', 'hoard_after') THEN
              CREATE TRIGGER hoard_after
                AFTER INSERT OR UPDATE OR DELETE ON %1\$s.triggers
                FOR EACH ROW 
                EXECUTE FUNCTION %1\$s.initialize();
            END IF;
          END;
          PLPGSQL,
        HoardSchema::$cacheSchema
      )),
    ]);
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
    DB::statement('DROP SCHEMA IF EXISTS hoard CASCADE;');
  }
};
