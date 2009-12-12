/*
Copyright (c) 2009 Brandon Williams

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/


-- phpMyAdmin SQL Dump
-- version 3.2.0.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 12, 2009 at 12:50 AM
-- Server version: 5.1.37
-- PHP Version: 5.3.0

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `dba`
--

-- --------------------------------------------------------

--
-- Table structure for table `benchmarks`
--
-- Creation: Dec 10, 2009 at 02:40 PM
--

DROP TABLE IF EXISTS `benchmarks`;
CREATE TABLE `benchmarks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `duration` varchar(10) NOT NULL,
  `user` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `results`
--
-- Creation: Dec 10, 2009 at 02:42 PM
--

DROP TABLE IF EXISTS `results`;
CREATE TABLE `results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server` char(16) NOT NULL,
  `type` varchar(7) NOT NULL,
  `min` float NOT NULL,
  `avg` float NOT NULL,
  `max` float NOT NULL,
  `std.dev` float NOT NULL,
  `reliab` float NOT NULL,
  `benchmark` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `servers`
--
-- Creation: Dec 10, 2009 at 03:51 PM
--

DROP TABLE IF EXISTS `servers`;
CREATE TABLE `servers` (
  `ip` char(16) NOT NULL,
  `reverse` varchar(50) NOT NULL,
  `owner` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL,
  PRIMARY KEY (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
