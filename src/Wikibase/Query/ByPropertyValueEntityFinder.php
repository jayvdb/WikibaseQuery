<?php

namespace Wikibase\Query;

use Ask\Language\Description\SomeProperty;
use Ask\Language\Description\ValueDescription;
use Ask\Language\Option\QueryOptions;
use DataValues\DataValue;
use DataValues\DataValueFactory;
use InvalidArgumentException;
use RuntimeException;
use Wikibase\EntityId;
use Wikibase\Lib\EntityIdParser;
use Wikibase\QueryEngine\QueryEngine;

/**
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseQuery
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ByPropertyValueEntityFinder {

	protected $queryEngine;
	protected $dvFactory;

	public function __construct( QueryEngine $queryEngine, DataValueFactory $dvFactory, EntityIdParser $idParser ) {
		$this->queryEngine = $queryEngine;
		$this->dvFactory = $dvFactory;
		$this->idParser = $idParser;
	}

	public function findEntities( array $requestArguments ) {
		return $this->findEntitiesGivenRawArguments(
			$requestArguments['property'],
			$requestArguments['value'],
			$requestArguments['limit'],
			$requestArguments['offset']
		);
	}

	/**
	 * @param string $propertyIdString
	 * @param string $valueString
	 * @param string $limit
	 * @param string $offset
	 *
	 * @return EntityId[]
	 * @throws InvalidArgumentException
	 */
	protected function findEntitiesGivenRawArguments( $propertyIdString, $valueString, $limit, $offset ) {
		if ( !is_string( $limit ) || !ctype_digit( $limit ) || (int)$limit < 1 ) {
			throw new InvalidArgumentException( '$limit needs to be a string representing a strictly positive integer' );
		}

		if ( !is_string( $offset ) || !ctype_digit( $offset ) ) {
			throw new InvalidArgumentException( '$offset needs to be a string representing a positive integer' );
		}

		if ( !is_string( $valueString ) ) {
			throw new InvalidArgumentException( '$valueString needs to be a string serialization of a DataValue' );
		}

		$valueSerialization = json_decode( $valueString, true );

		if ( !is_array( $valueSerialization ) ) {
			throw new InvalidArgumentException( 'The provided value needs to be a serialization of a DataValue' );
		}

		try {
			$propertyId = $this->idParser->parse( $propertyIdString );
			$value = $this->dvFactory->newFromArray( $valueSerialization );
		}
		catch ( RuntimeException $ex ) {
			throw new InvalidArgumentException( '', 0, $ex );
		}

		return $this->findByPropertyValue( $propertyId, $value, $limit, $offset );
	}

	/**
	 * @param EntityId $propertyId
	 * @param DataValue $value
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return EntityId[]
	 */
	protected function findByPropertyValue( EntityId $propertyId, DataValue $value, $limit, $offset ) {
		$description = new SomeProperty(
			$propertyId,
			new ValueDescription( $value )
		);

		$options = new QueryOptions( $limit, $offset );

		return $this->queryEngine->getMatchingEntities( $description, $options );
	}

}
