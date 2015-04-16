<?php
namespace TYPO3\TYPO3CR\Domain\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Exception\NodeTypeNotFoundException;

/**
 * Manager for node types
 *
 * @Flow\Scope("singleton")
 * @api
 */
class NodeTypeManager {

	/**
	 * Node types, indexed by name
	 *
	 * @var array
	 */
	protected $cachedNodeTypes = array();

	/**
	 * Node types, indexed by supertype
	 *
	 * @var array
	 */
	protected $cachedSubNodeTypes = array();

	/**
	 * @Flow\Inject
	 * @var ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * Return all registered node types.
	 *
	 * @param boolean $includeAbstractNodeTypes Whether to include abstract node types, defaults to TRUE
	 * @return array<NodeType> All node types registered in the system, indexed by node type name
	 * @api
	 */
	public function getNodeTypes($includeAbstractNodeTypes = TRUE) {
		if ($this->cachedNodeTypes === array()) {
			$this->loadNodeTypes();
		}
		if ($includeAbstractNodeTypes) {
			return $this->cachedNodeTypes;
		} else {
			$nonAbstractNodeTypes = array();
			foreach ($this->cachedNodeTypes as $nodeTypeName => $nodeType) {
				if (!$nodeType->isAbstract()) {
					$nonAbstractNodeTypes[$nodeTypeName] = $nodeType;
				}
			}
			return $nonAbstractNodeTypes;
		}
	}

	/**
	 * Return all non-abstract node types which have a certain $superType, without
	 * the $superType itself.
	 *
	 * @param string $superTypeName
	 * @param boolean $includeAbstractNodeTypes Whether to include abstract node types, defaults to TRUE
	 * @return array<NodeType> Sub node types of the given super type, indexed by node type name
	 * @api
	 */
	public function getSubNodeTypes($superTypeName, $includeAbstractNodeTypes = TRUE) {
		if ($this->cachedNodeTypes === array()) {
			$this->loadNodeTypes();
		}

		if (!isset($this->cachedSubNodeTypes[$superTypeName])) {
			$filteredNodeTypes = array();
			/** @var NodeType $nodeType */
			foreach ($this->cachedNodeTypes as $nodeTypeName => $nodeType) {
				if ($includeAbstractNodeTypes === FALSE && $nodeType->isAbstract()) {
					continue;
				}
				if ($nodeType->isOfType($superTypeName) && $nodeTypeName !== $superTypeName) {
					$filteredNodeTypes[$nodeTypeName] = $nodeType;
				}
			}
			$this->cachedSubNodeTypes[$superTypeName] = $filteredNodeTypes;
		}

		return $this->cachedSubNodeTypes[$superTypeName];
	}

	/**
	 * Returns the specified node type (which could be abstract)
	 *
	 * @param string $nodeTypeName
	 * @return NodeType or NULL
	 * @throws NodeTypeNotFoundException
	 * @api
	 */
	public function getNodeType($nodeTypeName) {
		if ($this->cachedNodeTypes === array()) {
			$this->loadNodeTypes();
		}
		if (!isset($this->cachedNodeTypes[$nodeTypeName])) {
			throw new NodeTypeNotFoundException('The node type "' . $nodeTypeName . '" is not available.', 1316598370);
		}
		return $this->cachedNodeTypes[$nodeTypeName];
	}

	/**
	 * Checks if the specified node type exists
	 *
	 * @param string $nodeTypeName Name of the node type
	 * @return boolean TRUE if it exists, otherwise FALSE
	 * @api
	 */
	public function hasNodeType($nodeTypeName) {
		if ($this->cachedNodeTypes === array()) {
			$this->loadNodeTypes();
		}
		return isset($this->cachedNodeTypes[$nodeTypeName]);
	}

	/**
	 * Creates a new node type
	 *
	 * @param string $nodeTypeName Unique name of the new node type. Example: "TYPO3.Neos:Page"
	 * @return NodeType
	 * @throws \TYPO3\TYPO3CR\Exception
	 */
	public function createNodeType($nodeTypeName) {
		throw new \TYPO3\TYPO3CR\Exception('Creation of node types not supported so far; tried to create "' . $nodeTypeName . '".', 1316449432);
	}

	/**
	 * Loads all node types into memory.
	 *
	 * @return void
	 */
	protected function loadNodeTypes() {
		$completeNodeTypeConfiguration = $this->configurationManager->getConfiguration('NodeTypes');
		foreach (array_keys($completeNodeTypeConfiguration) as $nodeTypeName) {
			$this->loadNodeType($nodeTypeName, $completeNodeTypeConfiguration);
		}
	}

	/**
	 * This method can be used by Functional of Behavioral Tests to completely
	 * override the node types known in the system.
	 *
	 * In order to reset the node type override, an empty array can be passed in.
	 * In this case, the system-node-types are used again.
	 *
	 * @param array $completeNodeTypeConfiguration
	 * @return void
	 */
	public function overrideNodeTypes(array $completeNodeTypeConfiguration) {
		$this->cachedNodeTypes = array();
		foreach (array_keys($completeNodeTypeConfiguration) as $nodeTypeName) {
			$this->loadNodeType($nodeTypeName, $completeNodeTypeConfiguration);
		}
	}

	/**
	 * Load one node type, if it is not loaded yet.
	 *
	 * @param string $nodeTypeName
	 * @param array $completeNodeTypeConfiguration the full node type configuration for all node types
	 * @return NodeType
	 * @throws \TYPO3\TYPO3CR\Exception
	 */
	protected function loadNodeType($nodeTypeName, array $completeNodeTypeConfiguration) {
		if (isset($this->cachedNodeTypes[$nodeTypeName])) {
			return $this->cachedNodeTypes[$nodeTypeName];
		}

		if (!isset($completeNodeTypeConfiguration[$nodeTypeName])) {
			throw new \TYPO3\TYPO3CR\Exception('Node type "' . $nodeTypeName . '" does not exist', 1316451800);
		}

		$nodeTypeConfiguration = $completeNodeTypeConfiguration[$nodeTypeName];

		$superTypes = array();
		if (isset($nodeTypeConfiguration['superTypes'])) {
			foreach ($nodeTypeConfiguration['superTypes'] as $key => $superTypeName) {

				// when removing supertypes by setting them to null, only string keys can be overridden
				if ($superTypeName === NULL && is_string($key)) {
					$superTypes[$key] = NULL;
					continue;
				} elseif ($superTypeName === NULL) {
					throw new \TYPO3\TYPO3CR\Exception\NodeConfigurationException('Node type "' . $nodeTypeName . '" sets supertype with a non-string key to NULL.', 1416578395);
				}

				$superType = $this->loadNodeType($superTypeName, $completeNodeTypeConfiguration);
				if ($superType->isFinal() === TRUE) {
					throw new \TYPO3\TYPO3CR\Exception\NodeTypeIsFinalException('Node type "' . $nodeTypeName . '" has a supertype "' . $superType->getName() . '" which is final.', 1316452423);
				}

				if (is_string($key)) {
					$superTypes[$key] = $superType;
				} else {
					$superTypes[] = $superType;
				}
			}
		}

		// Remove unset properties
		if (isset($nodeTypeConfiguration['properties'])) {
			foreach ($nodeTypeConfiguration['properties'] as $propertyName => $propertyConfiguration) {
				if ($propertyConfiguration === NULL) {
					unset($nodeTypeConfiguration['properties'][$propertyName]);
				}
			}
			if ($nodeTypeConfiguration['properties'] === array()) {
				unset($nodeTypeConfiguration['properties']);
			}
		}

		$nodeType = new NodeType($nodeTypeName, $superTypes, $nodeTypeConfiguration);

		$this->cachedNodeTypes[$nodeTypeName] = $nodeType;
		return $nodeType;
	}
}
