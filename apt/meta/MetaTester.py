'''
Created on Jun 2, 2010

@author: ola
'''

import token,tokenize,string, StringIO

_parseMap = {
    "int" :     "p.getInt()",
    "double":   "p.getDouble()",
    "long":     "p.getLong()",
    "String":   "p.getString()",
    "int[]":    "p.parseVI()",
    "double[]": "p.parseVD()",
    "String[]": "p.parseVS()"
}

_matchMap = {
    "int" :     "p.matchInt",
    "double" :  "p.matchDouble",
    "long" :    "p.matchLong",
    "String" :  "p.matchString",
    "int[]" :   "p.matchIntList",
    "double[]": "p.matchDoubleList",
    "String[]": "p.matchStringList"
}

_typeMap = {
    "int" :     "types.IntType",
    "float" :   "types.FloatType",
    "double":   "types.FloatType",
    "String" :  "types.StringType",
    "long"   :  "types.LongType",
    "[]" :      "types.ListType"
}

class MetaTester(object):
    '''
    classdocs
    '''

    

    def __init__(self):
        '''
        Constructor
        '''
        self.myMap = {}
        self.myArgs = []
        self.tags = ["return:","class:","method:","arg:"]

        self.readSpec()
        self.loadMap()
        
          
               
    def loadMap(self):
        self.myMap["RETURN"] = self.myMap["return:"]
        self.myMap["METHOD"] = self.myMap["method:"]
        self.myMap["CLASS"] = self.myMap["class:"]
        self.myMap["QTQT"] = "\""
        self.myMap["MATCH"] = "match"
        
        argparse1 = ""
        argparse2 = ""
        argcall = ""
        printargs = ""
        varname = 'a'
        
        for arg in self.myArgs:
            #print "processing "+arg
            argparse1 += varname + ","
            argparse2 += _parseMap[arg] + ","
            argcall += "copy.copy(" + varname +"),"
            printargs += 'sprinter.write(str('+varname + ")+' '); "
            varname = chr(ord(varname)+1)
        
        self.myMap["ARGPARSE"] = argparse1[:-1] + " = " + argparse2[:-1]
        self.myMap["ARGCALL"] = argcall[:-1]   # no last comma
        self.myMap["PRINTARGS"] = printargs
        ret = self.myMap["RETURN"]
        if ret.endswith("[]"):
            self.myMap["RETURN_TYPE"] = _typeMap["[]"]
        else:
            self.myMap["RETURN_TYPE"] = _typeMap[ret]
        
        #self.myMap["RETURN_TYPE"] = "str(" + self.myMap["RETURN_TYPE"] + ")"
        
        
        
    
    def readSpec(self):
        file = open("prob.spec",'r')
        for line in file:
            for tag in self.tags:
                if line.startswith(tag):
                    stag = line[0:len(tag)]
                    val = line[len(tag):].strip()
                    if stag == "arg:":
                        self.myArgs.append(val)
                    else:
                        self.myMap[tag] = val
    
    
    def printSpec(self):

        for tag in self.tags[:-1]:
            print tag,' ',
            if tag in self.myMap:
                print self.myMap.get(tag)
            else:
                print "not parsed"
        
        print "arguments:"
        for arg in self.myArgs:
            print arg,' ',
        print
        
    def tabify(self,data):
        ''' 
        from moinmoin tokenizer example
        '''
        self.raw = string.strip(string.expandtabs(data))
        #print "read ",len(self.raw)
        self.lines = [0,0]
        pos = 0
        while True:
            pos = string.find(self.raw,'\n',pos) + 1
            if not pos: break
            self.lines.append(pos)
        self.lines.append(len(self.raw))
        
        
        
    def writeFile(self):
        outFile = file("Tester.py","w")
        inFile = file("TesterSkel.py","r")
        self.tabify(inFile.read())
        
        #print "size of list: ",len(self.lines),"size of text ",len(self.raw)
        
        self.pos = 0
        text = StringIO.StringIO(self.raw)
        
        tokens = tokenize.generate_tokens(text.readline)
        for item in tokens:
            toktype,toktext,(srow,scol),(erow,ecol),line = item
            
            #print token.tok_name[toktype],toktext
            
            oldpos = self.pos
            newpos = self.lines[srow]+scol
            self.pos = newpos + len(toktext)
            
            #print "START",oldpos,newpos,self.pos,token.tok_name[toktype],toktext," then ",
            
            if toktype in [token.NEWLINE,tokenize.NL]:
                outFile.write('\n')
                continue
            
            # write out whitespace if needed
            if newpos > oldpos:
                outFile.write(self.raw[oldpos:newpos])
            
            #don't process indenting/dedenting tokens
            if toktype in [token.INDENT,token.DEDENT]:
                self.pos = newpos
                continue
            
            #print "token = ",toktext
            
            if toktext in self.myMap:
                str = self.myMap[toktext]
                if toktext == "MATCH":
                    str = _matchMap[self.myMap["RETURN"]]
                outFile.write(str)
                
            else:
                outFile.write(toktext)
                
        outFile.close()
             
            
m = MetaTester()
m.readSpec()
m.printSpec()
m.writeFile()
                        
                
        
