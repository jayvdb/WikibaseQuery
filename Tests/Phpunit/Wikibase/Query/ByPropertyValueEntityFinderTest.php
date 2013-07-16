<?php

namespace Tests\Phpunit\Wikibase\Query;

use Ask\Language\Description\AnyValue;
use Ask\Language\Description\Description;
use Ask\Language\Description\SomeProperty;
use Ask\Language\Description\ValueDescription;
use Ask\Language\Option\QueryOptions;
use DataValues\DataValue;
use DataValues\DataValueFactory;
use DataValues\StringValue;
use Wikibase\EntityId;
use Wikibase\Query\ByPropertyValueEntityFinder;

/**
 * @covers Wikibase\Query\ByPropertyValueEntityFinder
 *
 * @file
 * @ingroup WikibaseQuery
 * @group WikibaseQuery
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ByPropertyValueEntityFinderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider queryProvider
	 */
	public function testFindEntities( $propertyIdString, $dataValueSerialization, Description $description, QueryOptions $options ) {
		$expectedIds = array(
			new EntityId( 'hax', 1337 )
		);

		$entityFinder = $this->newEntityFinder( $description, $options, $expectedIds, $propertyIdString );

		$entityIds = $entityFinder->findEntities( array(
			'property' => $propertyIdString,
			'value' => json_encode( $dataValueSerialization ),
			'limit' => (string)$options->getLimit(),
			'offset' => (string)$options->getOffset()
		) );

		$this->assertEntityIdsEqual( $expectedIds, $entityIds );
	}

	protected function newEntityFinder( $description, $options, $expectedIds, $propertyIdString ) {
		$queryEngine = $this->getMock( 'Wikibase\QueryEngine\QueryEngine' );

		$queryEngine->expects( $this->once() )
			->method( 'getMatchingEntities' )
			->with(
				$this->equalTo( $description ),
				$this->equalTo( $options )
			)
			->will( $this->returnValue( $expectedIds ) );

		$dvFactory = new DataValueFactory();
		$dvFactory->registerDataValue( 'string', 'DataValues\StringValue' );

		$idParser = $this->getMockBuilder( 'Wikibase\Lib\EntityIdParser' )
			->disableOriginalConstructor()->getMock();

		$idParser->expects( $this->once() )
			->method( 'parse' )
			->with( $this->equalTo( $propertyIdString ) )
			->will( $this->returnValue( $this->mockProperty() ) );

		return new ByPropertyValueEntityFinder( $queryEngine, $dvFactory, $idParser );
	}

	protected function mockProperty() {
		return new EntityId( 'property', 4242 );
	}

	protected function assertEntityIdsEqual( array $expected, $actual ) {
		$this->assertInternalType( 'array', $actual );
		$this->assertContainsOnlyInstancesOf( 'Wikibase\EntityId', $actual );
		$this->assertEquals( $expected, $actual );
	}

	public function queryProvider() {
		$argLists = array();

		$fooString = new StringValue( 'foo' );
		$barString = new StringValue( 'bar baz' );

		$argLists[] = array(
			'p42',
			$fooString->toArray(),
			new SomeProperty(
				$this->mockProperty(),
				new ValueDescription( $fooString )
			),
			new QueryOptions( 10, 0 )
		);

		$argLists[] = array(
			'p9001',
			$barString->toArray(),
			new SomeProperty(
				$this->mockProperty(),
				new ValueDescription( $barString )
			),
			new QueryOptions( 42, 100 )
		);

		return $argLists;
	}

	/**
	 * @dataProvider invalidLimitProvider
	 */
	public function testInvalidLimitCausesException( $limit ) {
		$this->setExpectedException( 'InvalidArgumentException' );

		$fooString = new StringValue( 'foo' );

		$this->newSimpleEntityIdFiner()->findEntities(
			array(
				'property' => $this->mockProperty()->toArray(),
				'value' => json_encode( $fooString->toArray() ),
				'limit' => $limit,
				'offset' => '0'
			)
		);
	}

	protected function newSimpleEntityIdFiner() {
		$queryEngine = $this->getMock( 'Wikibase\QueryEngine\QueryEngine' );

		$queryEngine->expects( $this->any() )
			->method( 'getMatchingEntities' )
			->will( $this->returnValue( array() ) );

		$dvFactory = new DataValueFactory();
		$dvFactory->registerDataValue( 'string', 'DataValues\StringValue' );

		$idParser = $this->getMockBuilder( 'Wikibase\Lib\EntityIdParser' )
			->disableOriginalConstructor()->getMock();

		$idParser->expects( $this->any() )
			->method( 'parse' )
			->will( $this->returnValue( $this->mockProperty() ) );

		return new ByPropertyValueEntityFinder( $queryEngine, $dvFactory, $idParser );
	}

	public function invalidLimitProvider() {
		return array(
			array( 10 ),
			array( 4.2 ),
			array( '' ),
			array( '4.2' ),
			array( '-2' ),
			array( '0' ),
		);
	}

	/**
	 * @dataProvider invalidOffsetProvider
	 */
	public function testInvalidOffsetCausesException( $offset ) {
		$this->setExpectedException( 'InvalidArgumentException' );

		$fooString = new StringValue( 'foo' );

		$this->newSimpleEntityIdFiner()->findEntities(
			array(
				'property' => $this->mockProperty()->toArray(),
				'value' => json_encode( $fooString->toArray() ),
				'limit' => '100',
				'offset' => $offset
			)
		);
	}

	public function invalidOffsetProvider() {
		return array(
			array( 10 ),
			array( 4.2 ),
			array( '' ),
			array( '4.2' ),
			array( '-2' ),
		);
	}

	/**
	 * @dataProvider invalidValueProvider
	 */
	public function testInvalidValueCausesException( $value ) {
		$this->setExpectedException( 'InvalidArgumentException' );

		$this->newSimpleEntityIdFiner()->findEntities(
			array(
				'property' => $this->mockProperty()->toArray(),
				'value' => $value,
				'limit' => '100',
				'offset' => '0'
			)
		);
	}

	public function invalidValueProvider() {
		return array(
			array( array() ),
			array( null ),
			array( true ),
			array( array( 'foo' ) ),
			array( 'foo' ),
			array( json_encode( 'foo' ) ),
			array( json_encode( array( 'foo' ) ) ),
		);
	}

}
