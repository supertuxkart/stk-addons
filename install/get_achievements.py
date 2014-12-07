#!/bin/env python

import sys
from xml.dom import minidom

if __name__ == "__main__":
    if len(sys.argv) < 2:
        exit("Usage: %s <achievements.xml>" % (sys.argv[0]))
    
    filename = sys.argv[1]
    xmldoc = minidom.parse(filename)
    itemlist = xmldoc.getElementsByTagName('achievement')

    print("INSERT INTO `v2_achievements` (`id`,`name`) VALUES")
    for s in itemlist:
        if s == itemlist[-1]:
            sep = ";"
        else:
            sep = ","
        s_escaped = s.attributes['title'].value.replace("'", "''")
        print("    (%s, '%s')%s" % (s.attributes['id'].value, s_escaped, sep))