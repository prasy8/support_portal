<?php
//======= Class to remove single quote from string =======//
class singleQuoteRemove
{
	
	//Cross-Site security helper function
	function escapeSecurityHelper($value)
	{
		//htmlspecialchars 	=> converts special characters into their corresponding HTML entities. 
		//					   For example, it turns < into &lt; and > into &gt;. This ensures that if a user types something 
		//					   like <script>, the browser will safely print the text <script> on the screen instead of executing
		//					   it as actual code.
		//ENT_QUOTES 		=> It tells PHP to convert both double quotes (") and single quotes (') into HTML entities. 
		//UTF-8		 		=> This explicitly tells the function to handle the text using the UTF-8 character encoding, 
		// 					   which is the modern standard for web pages and supports almost all world languages and symbols.
		return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
	}
	
	function singleQuoteRemoveFun($string)			
	{
        if(!empty($string))
        {
            
            //$string=trim($string);
            //$string = stripslashes($string);	

            //$string = str_replace("</script>","",$string); 
            //$string = str_replace("<script>","",$string); 
            //$string = str_replace("</script>","",$string); 
            $string = str_replace("onerror","",$string); 
            $string = str_replace("onload","",$string); 
            $string = str_replace("alert(","",$string); 
            $string = str_replace("alert(","",$string); 
            $string = str_replace("prompt(","",$string); 
            
            
            
            $string = str_replace("\'","&#39;",$string);
            $string = str_replace("'","&#39;",$string);
            $string = str_replace('"','&#34;',$string); 
            $string = str_replace('\"','&#34;',$string); 
            //$string = str_replace('(','&#40;',$string); 
            //$string = str_replace(')','&#41;',$string); 
            //$string = str_replace("<","&#60;",$string);   
            //$string = str_replace(">","&#62;",$string);   
            //$string = str_replace("=","&#61;",$string); 
            
            
            
            
        }
		
		return $string;
	}
    
    function validateRequestIDNum($reqString)			
	{
        $string="Error-Req";
        if(!empty($reqString))
        {
            $string=trim($reqString);
            $string=(int)filter_var($string, FILTER_SANITIZE_NUMBER_INT);
            if(strlen($string)!==strlen($reqString))
            {
                $string="Error-Req";
            }
        }
		
		return $string;
	}
}
//======= Class to remove single quote from string =======//
?>