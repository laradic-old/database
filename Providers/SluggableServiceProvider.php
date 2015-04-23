<?php namespace Laradic\Database\Providers;


use Illuminate\Support\ServiceProvider;
use Laradic\Config\Traits\ConfigProviderTrait;
use Laradic\Contracts\Database\Sluggable;
use Laradic\Database\Console\SluggableTableCommand;
use Laradic\Database\Sluggable\SluggableMigrationCreator;

class SluggableServiceProvider extends ServiceProvider {

    use ConfigProviderTrait;
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{

	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        $this->addConfigComponent('laradic/database', 'laradic/database', __DIR__ . '/resources/config');
		$this->registerCreator();
		$this->registerEvents();
		$this->registerCommands();
	}



	/**
	 * Register the migration creator.
	 *
	 * @return void
	 */
	protected function registerCreator()
	{
		$this->app->singleton('sluggable.creator', function($app)
		{
			return new SluggableMigrationCreator($app['files']);
		});
	}

	/**
	 * Register the listener events
	 *
	 * @return void
	 */
	public function registerEvents()
	{
		$this->app['events']->listen('eloquent.saving*', function($model)
		{
			if ($model instanceof Sluggable)
			{
				$model->sluggify();
			}
		});
	}

	/**
	 * Register the artisan commands
	 *
	 * @return void
	 */
	public function registerCommands()
	{
		$this->app['sluggable.table'] = $this->app->share(function($app)
		{
			// Once we have the migration creator registered, we will create the command
			// and inject the creator. The creator is responsible for the actual file
			// creation of the migrations, and may be extended by these developers.
			$creator = $app['sluggable.creator'];

			$composer = $app['composer'];

			return new SluggableTableCommand($creator, $composer);
		});

		$this->commands('sluggable.table');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return ['sluggable.creator', 'sluggable.table'];
	}

}
