<?php

use Carbon\Carbon;
use Cassandra\Timestamp;
use lroman242\LaravelCassandra\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

class ModelTest extends TestCase
{
    public function tearDown()
    {
        User::truncate();
    }

    public function testNewModel()
    {
        $user = new User;
        $this->assertInstanceOf(Model::class, $user);
        $this->assertInstanceOf('lroman242\LaravelCassandra\Connection', $user->getConnection());
        $this->assertEquals(false, $user->exists);
        $this->assertEquals('users', $user->getTable());
        $this->assertEquals('id', $user->getKeyName());
    }

    public function testInsert()
    {
        $user = new User;
        $user->id = 1;
        $user->name = 'John Doe';
        $user->title = 'admin';
        $user->age = 35;

        $user->save();

        $this->assertEquals(true, $user->exists);
        $this->assertEquals(1, User::count());

        $this->assertTrue(isset($user->id));
        $this->assertInstanceOf(Carbon::class, $user->created_at);

        $raw = $user->getAttributes();
        $this->assertInstanceOf(Timestamp::class, $raw['created_at']);
        $this->assertInstanceOf(Timestamp::class, $raw['updated_at']);

        $this->assertEquals(1, $user->id);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals(35, $user->age);
    }

    public function testUpdate()
    {
        $user = new User;
        $user->id = 1;
        $user->name = 'John Doe';
        $user->title = 'admin';
        $user->age = 35;
        $user->save();

        $raw = $user->getAttributes();
        $this->assertEquals(1, $user->id);

        $check = User::find($user->id);

        $check->age = 36;
        $check->save();

        $this->assertEquals(true, $check->exists);
        $this->assertInstanceOf(Carbon::class, $check->created_at);
        $this->assertInstanceOf(Carbon::class, $check->updated_at);
        $this->assertEquals(1, User::count());

        $this->assertEquals('John Doe', $check->name);
        $this->assertEquals(36, $check->age);

        $user->update(['age' => 20]);

        $raw = $user->getAttributes();
        $this->assertEquals(1, $user->id);

        $check = User::find($user->id);
        $this->assertEquals(20, $check->age);
    }

    public function testManualIntId()
    {
        $user = new User;
        $user->id = 1;
        $user->name = 'John Doe';
        $user->title = 'admin';
        $user->age = 35;
        $user->save();

        $this->assertEquals(true, $user->exists);
        $this->assertEquals(1, $user->id);

        $raw = $user->getAttributes();
        $this->assertEquals(1, $user->id);
    }

    public function testDelete()
    {
        $user = new User;
        $user->id = 1;
        $user->name = 'John Doe';
        $user->title = 'admin';
        $user->age = 35;
        $user->save();

        $this->assertEquals(true, $user->exists);
        $this->assertEquals(1, User::count());

        $user->delete();

        $this->assertEquals(0, User::count());
    }

    public function testAll()
    {
        $user = new User;
        $user->id = 1;
        $user->name = 'John Doe';
        $user->title = 'admin';
        $user->age = 35;
        $user->save();

        $user = new User;
        $user->id = 2;
        $user->name = 'Jane Doe';
        $user->title = 'user';
        $user->age = 32;
        $user->save();

        $all = User::all();

        $this->assertEquals(2, count($all));
        $this->assertContains('John Doe', $all->pluck('name'));
        $this->assertContains('Jane Doe', $all->pluck('name'));
    }

    public function testFind()
    {
        $user = new User;
        $user->id = 1;
        $user->name = 'John Doe';
        $user->title = 'admin';
        $user->age = 35;
        $user->save();

        $check = User::find($user->id);

        $this->assertInstanceOf(Model::class, $check);
        $this->assertEquals(true, $check->exists);
        $this->assertEquals($user->id, $check->id);

        $this->assertEquals('John Doe', $check->name);
        $this->assertEquals(35, $check->age);
    }

    public function testGet()
    {
        User::insert([
            ['id' => 1, 'name' => 'John Doe'],
            ['id' => 2, 'name' => 'Jane Doe'],
        ]);

        $users = User::get();
        $this->assertEquals(2, count($users));
        $this->assertInstanceOf(Collection::class, $users);
        $this->assertInstanceOf(Model::class, $users[0]);
    }

    public function testBulkInsert()
    {
        User::insert([
            ['id' => 1, 'name' => 'John Doe'],
            ['id' => 2, 'name' => 'Jane Doe'],
        ]);

        $users = User::get();
        $this->assertEquals(2, count($users));

        $johnDoe = User::find(1);

        $this->assertEquals(true, $johnDoe->exists);
        $this->assertEquals('John Doe', $johnDoe->name);
        $this->assertEquals(1, $johnDoe->id);

        $janeDoe = User::find(2);

        $this->assertEquals(true, $janeDoe->exists);
        $this->assertEquals('Jane Doe', $janeDoe->name);
        $this->assertEquals(2, $janeDoe->id);
    }

    public function testFirst()
    {
        User::insert([
            ['id' => 1, 'name' => 'John Doe'],
            ['id' => 2, 'name' => 'Jane Doe'],
        ]);

        $user = User::first();
        $this->assertInstanceOf(Model::class, $user);
        $this->assertEquals('John Doe', $user->name);
    }

    public function testNoDocument()
    {
        $users = User::where('id', 999)->get();
        $this->assertInstanceOf(Collection::class, $users);
        $this->assertEquals(0, $users->count());

        $user = User::where('id', 999)->first();
        $this->assertEquals(null, $user);

        $user = User::find(999);
        $this->assertEquals(null, $user);
    }

    public function testFindOrfail()
    {
        $this->expectException(Illuminate\Database\Eloquent\ModelNotFoundException::class);
        User::findOrfail(999);
    }

    public function testCreate()
    {
        $user = User::create(['id' => 1, 'name' => 'Jane Poe']);

        $this->assertInstanceOf(Model::class, $user);
        $this->assertEquals(true, $user->exists);
        $this->assertEquals('Jane Poe', $user->name);

        $check = User::where('id', 1)->first();
        $this->assertEquals($user->id, $check->id);
    }

    public function testDestroy()
    {
        $user = new User;
        $user->id = 1;
        $user->name = 'John Doe';
        $user->title = 'admin';
        $user->age = 35;
        $user->save();

        User::destroy($user->id);

        $this->assertEquals(0, User::count());
    }

    public function testTouch()
    {
        $user = new User;
        $user->id = 1;
        $user->name = 'John Doe';
        $user->title = 'admin';
        $user->age = 35;
        $user->save();

        $old = $user->updated_at;

        sleep(1);
        $user->touch();
        $check = User::find($user->id);

        $this->assertNotEquals($old, $check->updated_at);
    }

    /**
     * TODO
     * I can't find a correct way to make soft delete works with Cassandra.
     * I'll have to revisit this eventually
     */
    // public function testSoftDelete()
    // {
    //     Soft::create(['id' => 1, 'name' => 'John Doe']);
    //     Soft::create(['id' => 2, 'name' => 'Jane Doe']);

    //     //$this->assertEquals(2, Soft::count());

    //     $user = Soft::where('id', 1)->first();
    //     $this->assertEquals(true, $user->exists);
    //     $this->assertEquals(false, $user->trashed());
    //     $this->assertNull($user->deleted_at);

    //     $user->delete();
    //     $this->assertEquals(true, $user->trashed());
    //     $this->assertNotNull($user->deleted_at);

    //     $user = Soft::where('id', 1)->first();
    //     $this->assertNull($user);

    //     $this->assertEquals(1, Soft::count());
    //     $this->assertEquals(2, Soft::withTrashed()->count());

    //     $user = Soft::withTrashed()->where('id', 1)->first();
    //     $this->assertNotNull($user);
    //     $this->assertInstanceOf(Carbon::class, $user->deleted_at);
    //     $this->assertEquals(true, $user->trashed());

    //     $user->restore();
    //     $this->assertEquals(2, Soft::count());
    // }

    public function testPrimaryKey()
    {
        $user = new User;
        $this->assertEquals('id', $user->getKeyName());

        $book = new Book;
        $this->assertEquals('title', $book->getKeyName());

        $book->title = 'A Game of Thrones';
        $book->author = 'George R. R. Martin';
        $book->save();

        $this->assertEquals('A Game of Thrones', $book->getKey());

        $check = Book::find('A Game of Thrones');
        $this->assertEquals('title', $check->getKeyName());
        $this->assertEquals('A Game of Thrones', $check->getKey());
        $this->assertEquals('A Game of Thrones', $check->title);
    }

    /**
     * TODO
     * Not sure if this is relevant... will come back to it
     */
    // public function testScope()
    // {
    //     Item::insert([
    //         ['name' => 'knife', 'type' => 'sharp'],
    //         ['name' => 'spoon', 'type' => 'round'],
    //     ]);

    //     $sharp = Item::sharp()->get();
    //     $this->assertEquals(1, $sharp->count());
    // }

    public function testToArray()
    {
        $item = Item::create(['id' => 1, 'name' => 'fork', 'type' => 'sharp']);

        $array = $item->toArray();
        $keys = array_keys($array);
        sort($keys);
        $this->assertEquals(['created_at', 'id', 'name', 'type', 'updated_at'], $keys);
        $this->assertTrue(is_string($array['created_at']));
        $this->assertTrue(is_string($array['updated_at']));
    }

    public function testDates()
    {
        $birthday = new DateTime('1980/1/1');
        $user = User::create(['id' => 1, 'name' => 'John Doe', 'birthday' => $birthday]);
        $this->assertInstanceOf(Carbon::class, $user->birthday);

        $check = User::find($user->id);
        $this->assertInstanceOf(Carbon::class, $check->birthday);
        $this->assertEquals($user->birthday, $check->birthday);

        $user = User::where('id', 1)->first();
        $this->assertEquals('John Doe', $user->name);

        // test custom date format for json output
        $json = $user->toArray();
        $this->assertEquals($user->birthday->format('l jS \of F Y h:i:s A'), $json['birthday']);
        $this->assertEquals($user->created_at->format('l jS \of F Y h:i:s A'), $json['created_at']);

        // test created_at
        $item = Item::create(['id' => 1, 'name' => 'sword']);
        $this->assertInstanceOf(Timestamp::class, $item->getOriginal('created_at'));
        $this->assertEquals($item->getOriginal('created_at')
                ->toDateTime()
                ->getTimestamp(), $item->created_at->getTimestamp());
        $this->assertTrue(abs(time() - $item->created_at->getTimestamp()) < 2);

        // test default date format for json output
        $item = Item::create(['id' => 1, 'name' => 'sword']);
        $json = $item->toArray();
        $this->assertEquals($item->created_at->format('Y-m-d H:i:s'), $json['created_at']);

        $user = User::create(['id' => 2, 'name' => 'Jane Doe', 'birthday' => time()]);
        $this->assertInstanceOf(Carbon::class, $user->birthday);

        $user = User::create(['id' => 2, 'name' => 'Jane Doe', 'birthday' => 'Monday 8th of August 2005 03:12:46 PM']);
        $this->assertInstanceOf(Carbon::class, $user->birthday);

        $user = User::create(['id' => 2, 'name' => 'Jane Doe', 'birthday' => '2005-08-08']);
        $this->assertInstanceOf(Carbon::class, $user->birthday);

        // TODO test UDT
        // $user = User::create(['id' => 2, 'name' => 'Jane Doe', 'entry' => ['date' => '2005-08-08']]);
        // $this->assertInstanceOf(Carbon::class, $user->getAttribute('entry.date'));

        // $user->setAttribute('entry.date', new DateTime);
        // $this->assertInstanceOf(Carbon::class, $user->getAttribute('entry.date'));

        // $data = $user->toArray();
        // $this->assertNotInstanceOf(UTCDateTime::class, $data['entry']['date']);
        // $this->assertEquals((string) $user->getAttribute('entry.date')->format('Y-m-d H:i:s'), $data['entry']['date']);
    }

    /**
     * TODO
     * I'm not even sure how the raw function works... I'll get back to this
     */
    // public function testRaw()
    // {
    //     User::create(['id' => 1, 'name' => 'John Doe', 'age' => 35]);
    //     User::create(['id' => 2, 'name' => 'Jane Doe', 'age' => 35]);
    //     User::create(['id' => 3, 'name' => 'Harry Hoe', 'age' => 15]);

    //     $users = User::raw(function ($collection) {
    //         return $collection->find(['age' => 35]);
    //     });

    //     //$this->assertInstanceOf(Collection::class, $users);
    //     $this->assertInstanceOf(Model::class, $users[0]);

    //     $user = User::raw(function ($collection) {
    //         return $collection->findOne(['age' => 35]);
    //     });

    //     $this->assertInstanceOf(Model::class, $user);

    //     $count = User::raw(function ($collection) {
    //         return $collection->count();
    //     });
    //     $this->assertEquals(3, $count);

    //     $result = User::raw(function ($collection) {
    //         return $collection->insertOne(['id' => 4, 'name' => 'Yvonne Yoe', 'age' => 35]);
    //     });
    //     $this->assertNotNull($result);
    // }

    /**
     * TODO
     * This is a problem that needs fixing
     */
    // public function testGetDirtyDates()
    // {
    //     $user = new User();
    //     $user->setRawAttributes(['name' => 'John Doe', 'birthday' => new DateTime('19 august 1989')], true);
    //     $this->assertEmpty($user->getDirty());

    //     $user->birthday = new DateTime('19 august 1989');
    //     $this->assertEmpty($user->getDirty());
    // }
}
