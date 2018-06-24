# MaxSchuster.CompressedStorage
A Neos.Flow package allowes you to compress PersistentResources in the gzip
format.

**Note: This package is still a work in progress and not ready for production** 

## Features
- Compress uploaded resources using git
- Automatically uncompress resources when accessing them
- Pass gziped resources to the client without decompressing them, if the
client supports gzip encoding

## License
MIT License

## Configuration
This package creates the **collection** `compressed` which combines the
**storage** `compressedPersistentResourcesStorage` and the **target**
`compressedPersistentResourcesTarget`.
