<?php
namespace TYPO3\Media\Domain\Model;

/*                                                                        *
 * This script belongs to the FLOW3 package "Media".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * An image interface
 */
interface ImageInterface {

	/**
	 * The title of this image
	 *
	 * @return string title of the image
	 */
	public function getTitle();

	/**
	 * Resource of the original image file
	 *
	 * @return \TYPO3\FLOW3\Resource\Resource
	 */
	public function getResource();

	/**
	 * Width of the image in pixels
	 *
	 * @return integer
	 */
	public function getWidth();

	/**
	 * Height of the image in pixels
	 *
	 * @return integer
	 */
	public function getHeight();

	/**
	 * One of PHPs IMAGETYPE_* constants that reflects the image type
	 *
	 * @see http://php.net/manual/image.constants.php
	 * @return integer
	 */
	public function getType();

	/**
	 * File extension of the image without leading dot.
	 *
	 * @return string
	 */
	public function getFileExtension();

	/**
	 * @param integer $maximumWidth maximum width of the thumbnail
	 * @param integer $maximumHeight maximum height of the thumbnail
	 * @return \TYPO3\Media\Domain\Model\ImageVariant
	 */
	public function getThumbnail($maximumWidth = NULL, $maximumHeight = NULL);

}

?>