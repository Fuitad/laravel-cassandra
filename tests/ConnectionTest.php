<?php

class ConnectionTest extends TestCase
{
    public function testConnection()
    {
        $connection = DB::connection('cassandra');
        $this->assertInstanceOf('fuitad\LaravelCassandra\Connection', $connection);
    }

    public function testReconnect()
    {
        $c1 = DB::connection('cassandra');
        $c2 = DB::connection('cassandra');
        $this->assertEquals(spl_object_hash($c1), spl_object_hash($c2));

        $c1 = DB::connection('cassandra');
        DB::purge('cassandra');
        $c2 = DB::connection('cassandra');
        $this->assertNotEquals(spl_object_hash($c1), spl_object_hash($c2));
    }

    public function testQueryLog()
    {
        DB::enableQueryLog();

        $this->assertEquals(0, count(DB::getQueryLog()));

        DB::table('testtable')->get();
        $this->assertEquals(1, count(DB::getQueryLog()));

        DB::table('testtable')->insert(['id' => 99, 'name' => 'test']);
        $this->assertEquals(2, count(DB::getQueryLog()));

        DB::table('testtable')->count();
        $this->assertEquals(3, count(DB::getQueryLog()));

        DB::table('testtable')->where('id', 99)->update(['name' => 'test']);
        $this->assertEquals(4, count(DB::getQueryLog()));

        DB::table('testtable')->where('id', 99)->delete();
        $this->assertEquals(5, count(DB::getQueryLog()));
    }

    public function testDriverName()
    {
        $driver = DB::connection('cassandra')->getDriverName();
        $this->assertEquals('cassandra', $driver);
    }
}
