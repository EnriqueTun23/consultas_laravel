<?php

use App\Models\Billing;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;



/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Buscar un Post por su ID

Route::get('find/{id}', function (int $id) {
    return Post::find($id);
});

// Buscar un post por su id o retornar un 404
Route::get('find-or-fail/{id}', function (int $id) {
    try {
        return Post::findOrFail($id);

    // } catch (Exception $e) {
    } catch (ModelNotFoundException $e) {
        return $e->getMessage();
    }
});


// Buscar un post por su id y selecciona columnas o retorna un 404

Route::get('find-or-fail-with-columns/{id}', function (int $id) {
    try {
        return Post::findOrFail($id, ['id', 'slug']);

        } catch (Exception $e) {
        return $e->getMessage();
    }
});

// Buscar un post por su slug o retornar un 404

Route::get('find-by-slug/{slug}', function (string $slug) {
    //e n lugar de
    // try {
    //     return Post::where('slug', $slug)->firstOrFail();
    // } catch (Exception $e) {
    //     return $e->getMessage();
    // }

    // Podemos hacer estos
    // return Post::whereSlug($slug)->firstOrFail();

    // O mejor aun
    return Post::firstWhere('slug', $slug);
});


// Buscar muchos posts por un array de ids

Route::get('find-many', function () {
    // en lugar de esto
    // return Post::whereIn('id', [1,2,3])->get();
    // haz lo siguiente

    // return Post::find([1,2,3]);
    return Post::find([1,2,3], ['id', 'title', 'likes']);
});


// Posts paginados con seleccion de columnas
Route::get('paginated/{perPage}', function (int $perPage = 10) {
    // return Post::paginate($perPage);
    return Post::paginate($perPage, ['id','title']);
});

// Post páginados manualmente con offset/limit

// /manual-paginado/2 -> primera pagina
// /manual-paginado/2/2 -> segunda pagina


Route::get('manual-paginado/{perPage}/{offset?}', function (int $perPage, int $offset = 0) {
    // return Post::offset($offset)->limit($perPage)->dd();
    return Post::offset($offset)->limit($perPage)->get();
});


// Crear un Post

Route::get('create', function () {
    $id_user = User::all()->random(1)->first()->id;
    $id_category = Category::all()->random(1)->first()->id;
    return Post::create([
        'user_id' => $id_user,
        'category_id' => $id_category,
        'title' => "Post para el usuario {$id_user} y categoria {$id_category}",
        'content' => "Post de pruebas"
    ]);
});


// Crear un post o si existe retornarlo

Route::get('first-or-create', function () {
    $title = "Post enrique Tun ";
    $id_user = User::all()->random(1)->first()->id;
    $id_category = Category::all()->random(1)->first()->id;
    return Post::firstOrCreate(
        ['title' => $title],
        [
            'user_id' => $id_user,
            'category_id' => $id_category,
            'title' => "Post para el usuario {$id_user} y categoria {$id_category}",
            'content' => "Post de pruebas"
        ]
    );
});

// Buscar un post y cargar su autor y tags con toda la informacion
Route::get('with-relations/{id}', function (int $id) {
    return Post::with('user', 'tags')->find($id);
});

// Buscar un post y carga su autor, categoria y tags con toda la informacion utilizando loaded
Route::get('with-relations-using-load/{id}', function (int $id) {
    $post = Post::findOrFail($id);
    $post->load('user', 'tags');
    return $post;
});


// Buscar un post y carga su autor, categoria y tags con seleccion de columnas en relaciones

Route::get('with-relations-and-columns/{id}', function (int $id) {
    return Post::select(["id", "user_id", "category_id", "title"])
    ->with([
        'user:id,name,email',
        'user.billing',
        'tags:id,tag',
        'category'
    ])
    ->find($id);
});


// Buscar un usuario y carga el numero de posts que tiene
Route::get('with-count-posts/{id}', function (int $id) {
    return User::select(['id', 'name', 'email'])
        ->withCount('posts')
        ->findOrFail($id);
        // ->toSql();
});


// Buscar un post o retornar un 404, pero si existe actualizarlo
Route::get('update/{id}', function (int $id) {
    // en lugar de hacer lo siguiente
    // $post = Post::findOrFail($id);
    // $post->title = "Post actualizado";
    // $post->save();
    // return $post;
    // haz lo siguiente
    return Post::findOrFail($id)->update([
        'title' => "Post actualizado de nuevo....",
    ]);
});

// Actualizar un post existente por su slug o lo crea si no existe
Route::get('update-or-create/{slug}', function (string $slug) {
    // en lugar
    // $post = Post::whereSlug($slug)->first()->id;
    // if ($post) {
    //     $post->update([
    //         'user_id' => User::all()->random(1)->first()->id,
    //         'title' => 'Post de pruebas',
    //         'content' => "haciendo algunas pruebas",
    //     ]);
    // } else {
    //     $post = Post::create([
    //         'user_id' => User::all()->random(1)->first()->id,
    //         'title' => 'Post de pruebas',
    //         'content' => "haciendo algunas pruebas",
    //     ]);
    // }

    // return $post;

    return Post::updateOrCreate([
        'slug' => $slug
    ],
    [
        'user_id' => User::all()->random(1)->first()->id,
        'category_id' => Category::all()->random(1)->first()->id,
        'title' => 'Actualizando o creando',
        'content' => "haciendo algunas pruebass",
    ]
);
});



// Eliminar un post y sus tags relacionados si existe

Route::get('delete-with-tags/{id}', function (int $id) {
    try {
        DB::beginTransaction();
        $post = Post::findOrFail($id);
        $post->tags()->detach();
        $post->delete();
        DB::commit();

        return $post;
    } catch (Exception $e) {
        DB::rollBack();
        return $e->getMessage();
    }
});


// Buscar un post o retorna un 404, pero si existe dale like
Route::get('like/{id}', function (int $id) {
    // en lugar de
    // $post = Post::findOrFail($id);
    // $post->likes++;
    // $post->save();

    // has lo siguiente
    return Post::findOrFail($id)->increment('likes', 5, [
        'title' => 'Post con muchos likes',
    ]);
});


// Buscar un post o retorna un 404, pero si existe dale dislike

Route::get('dislike/{id}', function (int $id) {
    // en lugar de
    // $post = Post::findOrFail($id);
    // $post->dislikes++;
    // $post->save();

    // has lo siguiente
    // return Post::findOrFail($id)->decrement('dislikes', 5, [
    //     'title' => 'Post con muchos likes',
    // ]);
    return Post::findOrFail($id)->increment('dislikes', 5, [
        'title' => 'Post con muchos likes',
    ]);
});


// Procesos complejos basados en chuncks
Route::get('chunk/{amount}', function (int $amount) {
    Post::chunk($amount, function (Collection $chunk){
        dd($chunk);
    });
});


// Crea un usuario y su informacion de pago
// si existe el usuario lo utiliza
// si existe el metodo de pago lo actualiza
Route::get('create-with-relation', function () {
    try {
        DB::beginTransaction();
        $user = User::firstOrCreate(
            ['name' => 'cursodesarrolloweb'],
            [
                'name' => 'cursodesarrolloweb',
                'age' => 25,
                'email' => 'eloquen@gmail.com',
                'password' => bcrypt('password')
            ]
        );
        // $user->billing()->updateOrCreate
        Billing::updateOrCreate(
            ['user_id' => $user->id],
            [
                'user_id' => $user->id,
                'credit_card_number' => '12334455'
            ]
        );
        DB::commit();
        return $user->load('billing:id,user_id,credit_card_number');
    } catch (Exception $e) {
        DB::rollBack();
        return $e->getMessage();
    }
});


// Actualizar un post y sus relaciones
Route::get('update-with-relation/{id}', function (int $id) {
    $post = Post::findOrFail($id);
    $post->title = 'Post actualizado update-with-relation ';
    $post->tags()->attach(Tag::all()->random(1)->first()->id);
    $post->save();
});


// Posts que tenga mas de 2 tags relacionados
Route::get('has-two-tags-or-more', function () {
    return Post::select(['id', 'title'])
        ->withCount('tags')
        ->has('tags', '>', 3)
        // sacar la consulta que estamos realizando
        // ->dd();
        ->get();
});


// Buscar un post  y cargar sus tags ordenados por nombre ascendentemente
// añadir relación sortedTags a modelo Post
Route::get('with-tags-sorted/{id}', function (int $id) {
    return Post::with('sortedTags:id,tag')
    ->find($id);
});


// Buscar todos los posts que tengan tags
Route::get('with-where-has-tags', function () {
    return Post::select(['id', 'title'])
    ->with('tags:id,tag')
    ->whereHas('tags')
    // -whereDoesntHave('tags')
    ->get();
});


// Scope para buscar todos los posts que tengan sortedTags
// añadir scopeWhereHasTagsWithTags

Route::get('scope-with-where-has-tags', function () {
    return Post::whereHasTagsWithTags()->get();
});


/**
 * Buscar un post y carga su autor de forma automatica y sus tags con toda la informacion utilizando
 *
 * añadir protected $with = ['user:id, name, email']; a modelo Post
 *
 */
Route::get('autoload-user-from-post-with-tags/{id}', function (int $id) {
    return Post::with('tags:id,tag')->findOrFail($id);
});


/**
 * post con atributos personalizados
 *
 * añadir getTitleWithAuthorAttribute a model Post
 */

 Route::get('custom-attributes/{id}', function (int $id) {
     return Post::with('user:id,name')->findOrFail($id);

 });


/**
 * Buscar  post por fecha de alta, valido formato Y-m-d
 *
 * /by-created-at/2022-08-17
 */

 Route::get('by-created-at/{date}', function (string $date) {
     return Post::whereDate('created_at', $date)
        ->get();
 });

/**
 * Buscar posts por día y mes en fecha de alta
 * /by-created-at-month-day/03/07
 *
 */
 Route::get('/by-created-at-month-day/{day}/{month}', function (int $day, int $month) {
      return Post::whereMonth('created_at', $month)
       ->whereDay('created_at', $day)
       ->get();
 });

/**
 * Buscar posts en un rango de fechas
 *
 * /between-by-created-at/2022-06-10/2022-06-30
 */

  Route::get('/between-by-created-at/{start}/{end}', function (string $start, string $end) {
      return Post::whereBetween('created_at', [$start, $end])->get();
  });

  /**
   * Obtiene todos los posts que el dia del mes sea superior a 5 o unos por slug si existe la querystring slug
   *
   * /when-slug?slug=<slug>
   *
   */
  Route::get("/when-slug", function () {
    return Post::whereMonth("created_at", now()->month)
    ->whereDay("created_at", ">", 1)
    ->when(request()->query("slug"), function (Builder $builder) {
        $builder->whereSlug(request()->query("slug"));
    })
    ->get();
  });

  /*
    SUBQUERIES PARA CONSULTAS AVANZADAS

    select * from `users` where (`banned` = true and `age` >= 50) or (`banned` = false and `age` <= 30)
  */

  Route::get("/subquery", function() {
    return User::where(function (Builder $builder) {
        $builder->where('banned', true)
        ->where('age', '>=', 40);
    })
    ->orWhere(function (Builder $builder) {
        $builder->where('banned', false)
            ->where('age', '<=', 30);
    })
    ->get();
  });

  /**
   * SCOPE GLOBAL EN POSTS PARA OBTENER SOLO POSTS DE ESTE MES
   *
   * añadir globalScope currentMonth a modelo Post
   */

   Route::get("/global-scope-posts-current-month", function () {
    return Post::count();
   });

   /**
    * DESHABILITAR SCOPE GLOBAL EN POSTS PARA OBTENER TODOS LOS POSTS
    */
   Route::get('/without-global-scope-posts-current-month', function () {
    return Post::withoutGlobalScope('currentMonth')->count();
   });

   /**
    * POSTS AGRUPADOS POR CATEGORIA CON SUMA DE LIKES Y DISLALIKES
    */
    Route::get('/quey-raw', function (){
        return Post::withoutGlobalScope('currentMonth')
        ->with("category")
        ->select([
            "id",
            "category_id",
            "likes",
            "dislikes",
            DB::raw("SUM(likes) as total_likes"),
            DB::raw("SUM(dislikes) as total_dislikes"),
        ])
        ->groupBy("category_id")
        ->get();
    });

    /***
     * POSTS AGRUPADOS POR CATEGORIA CON SUMA DE LIKES Y DISLIKES QUE SUMEN MAS DE 110 LIKES
     */

     Route::get('/quey-raw-having-raw', function (){
        return Post::withoutGlobalScope('currentMonth')
        ->with("category")
        ->select([
            "id",
            "category_id",
            "likes",
            "dislikes",
            DB::raw("SUM(likes) as total_likes"),
            DB::raw("SUM(dislikes) as total_dislikes")
        ])
        ->groupBy("category_id")
        ->havingRaw("SUM(likes) > ?", [500])
        ->get();
     });


     /**
      * USUARIOS ORDENADOS POR SU ULTIMO POST
      */
      Route::get('/order-by-subqueries', function () {
        return User::select(['id', 'name'])
        ->has('posts')
        ->orderByDesc(
            Post::withoutGlobalScope('currentMonth')
            ->select("created_at")
            ->whereColumn("user_id", "users.id")
            ->orderBy("created_at", "desc")
            ->limit(1)
            )
        ->get();
      });


      /** USUARIOS QUE TIENEN POSTS CON SU ULTIMO POST PUBLICADO */

      Route::get("/select-subqueries", function () {
        return User::select(["id", "name"])
            ->has("posts")
            ->addSelect([
                "last_post" => Post::withoutGlobalScope('currentMonth')
                ->select("title")
                ->whereColumn("user_id", "users.id")
                ->orderBy("created_at", "desc")
                ->limit(1)
            ])
            ->get();
      });

      /**
       * INSERT MASIVO DE USUARIOS
       */
        Route::get("/multiple-insert", function () {
            $users = new Collection;

            for ($i = 1; $i <= 20; $i++) {
                $users->push([
                    "name" => "usuario $i",
                    "email" => "usuario$i@yopmail.com",
                    "password" => bcrypt("password"),
                    "email_verified_at" => now(),
                    "created_at" => now(),
                    "age" => rand(20, 55)
                ]);
            }

            User::insert($users->toArray());

            return $users;
        });


    /** INSERT BATCH */
    Route::get("/batch-insert", function () {
        $userInstance =  new User;

        $col = [
            'name',
            'email',
            'password',
            'age',
            'banned',
            'email_verified_at',
            'created_at'
        ];

        $users = new Collection;

        for ($i=0; $i < 120 ; $i++) {
            $users->push([
                "usuario $i",
                "usuarios$i@yopmail.com",
                bcrypt("password"),
                rand(20,50),
                rand(0, 1),
                now(),
                now()
            ]);
        }

        $batchSize = 50; // insert 500 (default), 100 minimum rows in one query

        /** @var Mavinoo\Batch\Batch $batch */

        $batch = batch();

        return $batch->insert($userInstance, $col, $users->toArray(), $batchSize);

    });

    /**
     * UPDATE BATCH
     */

     Route::get("/batch-update", function() {
        $postInstance =  new Post;

        $toUpdate = [
            [
                "id" => 1,
                "likes" => ["*", 2], // multiplica
                "dislikes" => ["/", 2], // divide
            ],
            [
                "id" => 2,
                "likes" => ["-", 2], // resta
                "title" => "Nuevo titlulo"
            ],
            [
                "id" => 3,
                "likes" => ["+", 5], // suma
            ],
            [
                "id" => 4,
                "likes" => ["*", 2], // multiplica
            ],
        ];

        $index = "id";

        /** @var Mavinoo\Batch\Batch $batch */

        $batch = batch();

        return $batch->update($postInstance, $toUpdate, $index);
     });
