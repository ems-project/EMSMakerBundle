<?php

namespace EMS\MakerBundle\Service;

class JsonMakeFileParser
{
    /**
     * EMSCoreBundle already has the functionality to create everything we want to create via the MakerBundle Commands
     * We should refactor EMSCore to isolate services that allows us to:
     * * create analyzers
     * * create contenttypes
     * * create environments
     * * create revisions
     * * create users
     *
     * These services should then be used by the MakerBundle in order to create all the things
     *
     * The JsonMakeFileParser should be used to transform jsons to parameters for the exisiting CoreBundle services.
     * When converting a json to parameters, the languages should be taken into account.
     *
     * --> This functionality already exists for the import/export of content types, this should be kept in the core bundle (and refactored for reuse by the maker bundle)
     */
}
