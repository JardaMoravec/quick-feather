<?php

namespace Tests\Tool\db;

use PHPUnit\Framework\TestCase;
use QuickFeather\EntityManager\db;
use QuickFeather\EntityManager\Error\SQLError;


class dbTest extends TestCase {

	public function testIsNot(): void {
		$this->assertSame('table.c1 != table.c2', db::isNot('table.c1', 'table.c2'));
		$this->assertSame('table.c1 != 10', db::isNot('table.c1', '10'));
		$this->assertSame('table.c1 != \'test\'', db::isNot('table.c1', 'test', true));
		$this->assertSame('table.c1 != \'test(&apos;x1&apos;)\'', db::isNot('table.c1', "test('x1')", true));
	}

	public function testAnd(): void {
		$this->assertSame('(c1 = 10)', db::and('c1 = 10'));
		$this->assertSame('(c1 = 10 AND c2 = 20)', db::and('c1 = 10', 'c2 = 20'));
		$this->assertSame('(c1 = 10 AND c2 = 20 AND c3 = c2)', db::and('c1 = 10', 'c2 = 20', 'c3 = c2'));
		$this->assertSame('(c1 = 10 AND c2 = 20 AND (c1 = c2 OR c2 = c3))', db::and('c1 = 10', 'c2 = 20', db::or('c1 = c2', 'c2 = c3')));
	}

	public function testIsNull(): void {
		$this->assertSame('c1 is null', db::isNull('c1'));
		$this->assertSame('table1.c2 is null', db::isNull('table1.c2'));
	}

	public function testAny(): void {
		$this->assertSame('any(c1)', db::any('c1'));
	}

	public function testRightJoin(): void {
		$this->assertSame(" RIGHT JOIN table2 ON table1.c1 = table2.c1", db::rightJoin('table2', 'table1.c1 = table2.c1'));
		$this->assertSame(" RIGHT JOIN table2 alias2 ON table1.c1 = alias2.c1", db::rightJoin('table2', 'table1.c1 = alias2.c1', 'alias2'));
	}

	public function testCount(): void {
		$this->assertSame("count(table.c1)", db::count('table.c1', false));
		$this->assertSame("count(table.c1) as table_c1", db::count('table.c1'));
		$this->assertSame("count(table.c1) as minimum", db::count('table.c1', 'minimum'));
	}

	public function testSum(): void {
		$this->assertSame("sum(table.c1)", db::sum('table.c1', false));
		$this->assertSame("sum(table.c1) as table_c1", db::sum('table.c1'));
		$this->assertSame("sum(table.c1) as minimum", db::sum('table.c1', 'minimum'));
	}

	public function testAvg(): void {
		$this->assertSame("avg(table.c1)", db::avg('table.c1', false));
		$this->assertSame("avg(table.c1) as table_c1", db::avg('table.c1'));
		$this->assertSame("avg(table.c1) as average", db::avg('table.c1', 'average'));
		$this->assertSame("avg(table.c1)::integer as average", db::avg('table.c1', 'average', 'integer'));
	}

	public function testDelete(): void {
		$query = Db::delete('schema.table', 'id = 5');

		$this->assertSame("DELETE FROM schema.table WHERE id = 5", $query);
	}

	public function testGroupBy(): void {
		$this->assertSame("c1", db::groupBy('c1'));
		$this->assertSame("c1, c2, c3", db::groupBy('c1', 'c2', 'c3'));
	}

	public function testJsonProp(): void {
		$this->assertSame("c1->>'prop' as c1_prop", db::jsonProp('c1', 'prop'));
		$this->assertSame("c1->>'prop' as alias", db::jsonProp('c1', 'prop', 'alias'));
	}

	public function testOrderBy(): void {
		$this->assertSame("c1 ASC", db::orderBy(db::asc('c1')));
		$this->assertSame("c2 DESC", db::orderBy(db::desc('c2')));
		$this->assertSame("c1 ASC, c2 DESC", db::orderBy(db::asc('c1'), db::desc('c2')));
	}

	public function testOr(): void {
		$this->assertSame('(c1 = 10)', db::or('c1 = 10'));
		$this->assertSame('(c1 = 10 OR c2 = 20)', db::or('c1 = 10', 'c2 = 20'));
		$this->assertSame('(c1 = 10 OR c2 = 20 OR c3 = c2)', db::or('c1 = 10', 'c2 = 20', 'c3 = c2'));
		$this->assertSame('(c1 = 10 OR c2 = 20 OR (c1 = c2 OR c2 = c3))', db::or('c1 = 10', 'c2 = 20', db::or('c1 = c2', 'c2 = c3')));
	}

	public function testIsSmaller(): void {
		$this->assertSame("c1 < c2", db::isSmaller('c1', 'c2'));
		$this->assertSame("c1 < 10", db::isSmaller('c1', 10));
		$this->assertSame("20 < c2", db::isSmaller(20, 'c2'));
		$this->assertSame("c1 < '2022-12-24'", db::isSmaller('c1', '2022-12-24', true));
		$this->assertSame("'2021-12-24' < '2022-12-24'", db::isSmaller(db::aps('2021-12-24'), '2022-12-24', true));
	}

	public function testMd5(): void {
		$this->assertSame("md5(c1)", db::md5('c1'));
		$this->assertSame("md5('test')", db::md5('test', true));
		$this->assertSame("md5('test&apos;1&apos;')", db::md5('test\'1\'', true));
		$this->assertSame("md5('test\t')", db::md5("test\t", true));
		$this->assertSame("md5('test&#92;t')", db::md5("test\\t", true));
	}

	public function testCrossJoin(): void {
		$this->assertSame(" CROSS JOIN table2", db::crossJoin('table2'));
		$this->assertSame(" CROSS JOIN table2 alias2", db::crossJoin('table2', 'alias2'));
	}

	/**
	 * @throws SQLError
	 */
	public function testUpdate(): void {
		$table = 'public.users';
		$params = [
		  'name' => "'John'",
		  'age' => 25,
		  'email' => "'email@email.cz'",
		];
		$where = 'id = 5';

		$this->assertSame(
			'UPDATE public.users SET "name" = \'John\',"age" = 25,"email" = \'email@email.cz\' WHERE id = 5',
			db::update($table, $params, $where)
		);
	}

	/**
	 * @throws SQLError
	 */
	public function testIn(): void {
		$this->assertSame("c1 in (1,2,3,4,5)", db::in('c1', [1,2,3,4,5]));
	}

	public function testInException(): void {
		$this->expectException(SQLError::class);
		$r = db::in('c1', []);
	}

	public function testIsNotNull(): void {
		$this->assertSame('c1 is not null', db::isNotNull('c1'));
		$this->assertSame('table1.c2 is not null', db::isNotNull('table1.c2'));
	}

	public function testDesc(): void {
		$this->assertSame("c1 DESC", db::desc('c1'));
		$this->assertSame("table.c1 DESC", db::desc('table.c1'));
	}

	public function testAs(): void {
		$this->assertSame("c1 as new_name", db::as('c1', 'new_name'));
	}

	public function testIsBigger(): void {
		$this->assertSame("c1 > c2", db::isBigger('c1', 'c2'));
		$this->assertSame("c1 > 10", db::isBigger('c1', 10));
		$this->assertSame("20 > c2", db::isBigger(20, 'c2'));
		$this->assertSame("c1 > '2022-12-24'", db::isBigger('c1', '2022-12-24', true));
		$this->assertSame("'2021-12-24' > '2022-12-24'", db::isBigger(db::aps('2021-12-24'), '2022-12-24', true));
	}

	public function testLike(): void {
		$this->assertSame("c1 LIKE '%value%'", db::like('c1', 'value'));
		$this->assertSame("c1 LIKE '%value'", db::like('c1', '%value'));
		$this->assertSame("c1 LIKE 'value%'", db::like('c1', 'value%'));
		$this->assertSame("c1 LIKE '%'", db::like('c1', '%'));
		$this->assertSame("c1 LIKE 'a%b'", db::like('c1', 'a%b'));
		$this->assertSame("c1 LIKE 'a\\%b'", db::like('c1', 'a\%b'));
		$this->assertSame("c1 LIKE '%a'bc%'", db::like('c1', 'a\'bc'));
	}

	public function testNotLike(): void {
		$this->assertSame("c1 NOT LIKE '%value%'", db::notLike('c1', 'value'));
		$this->assertSame("c1 NOT LIKE '%value'", db::notLike('c1', '%value'));
		$this->assertSame("c1 NOT LIKE 'value%'", db::notLike('c1', 'value%'));
		$this->assertSame("c1 NOT LIKE '%'", db::notLike('c1', '%'));
		$this->assertSame("c1 NOT LIKE 'a%b'", db::notLike('c1', 'a%b'));
		$this->assertSame("c1 NOT LIKE 'a\\%b'", db::notLike('c1', 'a\%b'));
		$this->assertSame("c1 NOT LIKE '%a'bc%'", db::notLike('c1', 'a\'bc'));
	}

	public function testILike (): void {
		$this->assertSame("lower(c1) LIKE lower('%value%')", db::iLike('c1', 'value'));
		$this->assertSame("lower(c1) LIKE lower('%value')", db::iLike('c1', '%value'));
		$this->assertSame("lower(c1) LIKE lower('value%')", db::iLike('c1', 'value%'));
		$this->assertSame("lower(c1) LIKE lower('%')", db::iLike('c1', '%'));
		$this->assertSame("lower(c1) LIKE lower('a%b')", db::iLike('c1', 'a%b'));
		$this->assertSame("lower(c1) LIKE lower('a\\%b')", db::iLike('c1', 'a\%b'));
	}

	public function testNotILike(): void {
		$this->assertSame("lower(c1) NOT LIKE lower('%value%')", db::notILike('c1', 'value'));
		$this->assertSame("lower(c1) NOT LIKE lower('%value')", db::notILike('c1', '%value'));
		$this->assertSame("lower(c1) NOT LIKE lower('value%')", db::notILike('c1', 'value%'));
		$this->assertSame("lower(c1) NOT LIKE lower('%')", db::notILike('c1', '%'));
		$this->assertSame("lower(c1) NOT LIKE lower('a%b')", db::notILike('c1', 'a%b'));
		$this->assertSame("lower(c1) NOT LIKE lower('a\\%b')", db::notILike('c1', 'a\%b'));
	}

	public function testSmartLike(): void {
		$this->assertSame("unaccent(lower(c1)) LIKE unaccent(lower('%value%'))", db::smartLike('c1', 'value'));
		$this->assertSame("unaccent(lower(c1)) LIKE unaccent(lower('%value'))", db::smartLike('c1', '%value'));
		$this->assertSame("unaccent(lower(c1)) LIKE unaccent(lower('value%'))", db::smartLike('c1', 'value%'));
		$this->assertSame("unaccent(lower(c1)) LIKE unaccent(lower('%'))", db::smartLike('c1', '%'));
		$this->assertSame("unaccent(lower(c1)) LIKE unaccent(lower('a%b'))", db::smartLike('c1', 'a%b'));
		$this->assertSame("unaccent(lower(c1)) LIKE unaccent(lower('a\\%b'))", db::smartLike('c1', 'a\%b'));
	}

	public function testMin(): void {
		$this->assertSame("min(table.c1)", db::min('table.c1', false));
		$this->assertSame("min(table.c1) as table_c1", db::min('table.c1'));
		$this->assertSame("min(table.c1) as minimum", db::min('table.c1', 'minimum'));
	}

	public function testIs(): void {
		$this->assertSame("age = 25", db::is('age', 25));
		$this->assertSame("age = 25", db::is('age', 25, false));
		$this->assertSame("age = 25", db::is('age', 25, true));
		$this->assertSame("name = Karel", db::is('name', "Karel"));
		$this->assertSame("name = Karel", db::is('name', "Karel", false));
		$this->assertSame("name = 'Karel'", db::is('name', "Karel", true));
	}

	public function testBracket(): void {
		$this->assertSame("(table.c1)", db::bracket('table.c1'));
	}

	/**
	 * @throws SQLError
	 */
	public function testInsert(): void {
		$table = 'public.users';
		$params = [
		  'name' => "'John'",
		  'age' => 25,
		  'email' => "'john@example.com'",
		];
		$returning = ['id'];

		$this->assertSame(
			"INSERT INTO public.users (\"name\",\"age\",\"email\") VALUES ('John',25,'john@example.com') RETURNING id;",
			 Db::insert($table, $params, $returning));
	}

	public function testInnerJoin(): void {
		$this->assertSame(" INNER JOIN table2 ON table1.c1 = table2.c1", db::innerJoin('table2', 'table1.c1 = table2.c1'));
		$this->assertSame(" INNER JOIN table2 alias2 ON table1.c1 = alias2.c1", db::innerJoin('table2', 'table1.c1 = alias2.c1', 'alias2'));
	}

	public function testAsc(): void {
		$this->assertSame("c1 ASC", db::asc('c1'));
		$this->assertSame("table.c1 ASC", db::asc('table.c1'));
	}

	public function testMax(): void {
		$this->assertSame("max(table.c1)", db::max('table.c1', false));
		$this->assertSame("max(table.c1) as table_c1", db::max('table.c1'));
		$this->assertSame("max(table.c1) as minimum", db::max('table.c1', 'minimum'));
	}

	public function testAps(): void {
		$this->assertSame("'test'", db::aps('test'));
		$this->assertSame("'test\t'", db::aps("test\t"));
		$this->assertSame("'test&apos;s'", db::aps("test's"));
		$this->assertSame("'test1&#92;2'", db::aps("test1\\2"));
		$this->assertSame("'test1(&quot;value&quot;)'", db::aps("test1(\"value\")"));
	}

	/**
	 * @throws SQLError
	 */
	public function testSelect(): void {
		$this->assertSame(
			'SELECT users.name,users.age,users.email FROM users WHERE users.id = 5',
			db::select(['name', 'age', 'email'], 'users',  db::is('users.id', 5))
		);

		$this->assertSame(
			'SELECT users.name,users.age,users.email FROM users WHERE users.id = 5 GROUP BY id',
			Db::select(['name', 'age', 'email'], 'users',  db::is('users.id', 5), ['id'])
		);

		$this->assertSame(
			'SELECT users.name,users.age,users.email FROM users WHERE users.id = 5 ORDER BY name ASC',
			Db::select(['name', 'age', 'email'], 'users',  db::is('users.id', 5), orderBy: db::orderBy(db::asc('name')))
		);

		$this->assertSame(
			'SELECT users.name,users.age,users.email FROM users WHERE users.id = 5 ORDER BY name ASC LIMIT 10',
			Db::select(['name', 'age', 'email'], 'users',  db::is('users.id', 5), orderBy:  db::orderBy(db::asc('name')), limit: 10)
		);

		$this->assertSame(
			'SELECT users.name,users.age,users.email FROM users WHERE users.id = 5 ORDER BY name ASC LIMIT 10 OFFSET 5',
			Db::select(['name', 'age', 'email'], 'users',  db::is('users.id', 5), orderBy: db::orderBy(db::asc('name')), limit: 10, offset: 5)
		);
	}

	public function testArrayIndex(): void {
		$this->assertSame("test[1] as test", db::arrayIndex('test',1 ));
		$this->assertSame("test[2]", db::arrayIndex('test',2, false ));
		$this->assertSame("test[2] as ali", db::arrayIndex('test',2, 'ali' ));
	}

	public function testPkIs(): void {
		$this->assertSame("id = 10", db::pkIs(10));
	}

	public function testCh(): void {
		$this->assertSame("alias.c1", db::ch("table.c1", 'alias'));
	}

	/**
	 * @return void
	 * @throws SQLError
	 */
	public function testCoalesce(): void {
		$this->assertSame("coalesce(c1, c2, c3) as alias", db::coalesce(["c1",'c2','c3'], 'alias'));
	}
	public function testCoalesceException(): void {
		$this->expectException(SQLError::class);
		$r = db::coalesce([], 'c1');
	}

	public function testLeftJoin(): void {
		$this->assertSame(" LEFT JOIN table2 ON table1.c1 = table2.c1", db::leftJoin('table2', 'table1.c1 = table2.c1'));
		$this->assertSame(" LEFT JOIN table2 alias2 ON table1.c1 = alias2.c1", db::leftJoin('table2', 'table1.c1 = alias2.c1', 'alias2'));
	}
}
