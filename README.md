# PHP-libphono
============

Pronounced "/ lib fo no /", libphono is a PHP library used for E.164 phone number normalization. It is intended to be a small, fast way to work with telephone numbers.

## What is libphono?

The library is designed to be simple to use, small and fast. libphono is intended as a library which allows numbers to be converted from local or national format into an international number which conforms to the E 164 standard. This is achieved using a data set which contains information about published dial plans.

The library allows an input number to be converted into a variety of different formats, e.g. local, national or E.164 international.

In contrast to other implementations, logic for country specific dial plan specifics is encapsulated in the class itself and does not need to be handled by the developer. This brings us to...

## What is libphono not?

libphono is not intended to be a replacement for formatting libraries such as Google's "libphonenumber".  The goal of the project is not to provide specific geographic or carrier information about numbers or number formatting.

Specific information is not supported, such as:
* DOES NOT: provide specific information regarding geographic / carrier information about a number.
* DOES NOT: provide information about whether the number is a fixed line or mobile device is also not included.
* DOES NOT: convert numbers into formatted strings, e.g. "+1 (614) 544 5874".
* DOES NOT: provide plausability check for numbers - syntax check for specific countries, areas, number length, etc…

## How do I use libphono?

Please see the API documentation for a description of how to use the class. There is also a quickstart file included in the repository to help you get started.

## What countries / dial plans does libphono cover?

All countries which have publicly available information related to their dial plans. 235 countries are currently included.

## What's Required

A new version of PHP (the library has been tested with 5.1.6 and 5.3.3.
PHP must include the following modules:
- MySQL or SQLite3 (to provide access to data)
- [Tinned Framework](http://www.tinned-software.net/) (required files already included)

## Something not working right?

If there is missing or incorrect information please contact us.
